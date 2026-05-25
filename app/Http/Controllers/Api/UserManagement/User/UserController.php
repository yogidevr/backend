<?php

namespace App\Http\Controllers\Api\UserManagement\User;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = max(1, min((int) $request->query('per_page', 10), 100));
        $sortField = (string) $request->query('sort_field', 'id');
        $sortOrder = strtolower((string) $request->query('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSortFields = ['id', 'nama', 'email', 'role'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'id';
        }

        $query = User::query()
            ->with(['permissions' => fn ($permissionQuery) => $permissionQuery
                ->orderBy('group_name')
                ->orderBy('name')]);

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('role', 'like', '%' . $search . '%');
            });
        }

        $users = $query->orderBy($sortField, $sortOrder)->paginate($perPage);

        return response()->json([
            'message' => 'Daftar pengguna berhasil diambil.',
            'data' => collect($users->items())->map(fn (User $user) => $this->transformUser($user))->values(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $roleLabel = trim((string) $validated['role']);
        $roleType = $this->normalizeRole($roleLabel);
        $roleToStore = $roleType === 'superadmin' ? 'super_admin' : 'admin';
        $roleLabelToStore = $roleType === 'superadmin' ? 'Super Admin' : $roleLabel;

        $user = User::query()->create([
            'nama' => $validated['nama'],
            'name' => $validated['nama'],
            'email' => $validated['email'],
            'role' => $roleToStore,
            'role_label' => $roleLabelToStore,
            'password' => $validated['password'],
        ]);

        if ($roleType !== 'superadmin') {
            $permissionIds = array_key_exists('permission_ids', $validated)
                ? ($validated['permission_ids'] ?? [])
                : $this->defaultAdminPermissionIds();

            $user->permissions()->sync($permissionIds);
        }

        $user->load(['permissions' => fn ($permissionQuery) => $permissionQuery
            ->orderBy('group_name')
            ->orderBy('name')]);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan.',
            'data' => $this->transformUser($user),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['permissions' => fn ($permissionQuery) => $permissionQuery
            ->orderBy('group_name')
            ->orderBy('name')]);

        return response()->json([
            'message' => 'Detail pengguna berhasil diambil.',
            'data' => $this->transformUser($user),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $newRoleLabel = trim((string) $validated['role']);
        $newRole = $this->normalizeRole($newRoleLabel);
        $currentRole = $user->normalizedRole();
        $roleToStore = $newRole === 'superadmin' ? 'super_admin' : 'admin';
        $roleLabelToStore = $newRole === 'superadmin' ? 'Super Admin' : $newRoleLabel;

        if ($currentRole === 'superadmin' && $newRole !== 'superadmin' && $this->countSuperAdmins() <= 1) {
            return response()->json([
                'message' => 'Minimal harus ada satu superadmin aktif.',
            ], 422);
        }

        $user->fill([
            'nama' => $validated['nama'],
            'name' => $validated['nama'],
            'email' => $validated['email'],
            'role' => $roleToStore,
            'role_label' => $roleLabelToStore,
        ]);

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
            $user->api_token = null;
            $user->api_token_expires_at = null;
        }

        $user->save();

        if ($newRole === 'superadmin') {
            $user->permissions()->detach();
        } else {
            if (array_key_exists('permission_ids', $validated)) {
                $permissionIds = $validated['permission_ids'] ?? [];
            } elseif ($currentRole === 'superadmin') {
                $permissionIds = $this->defaultAdminPermissionIds();
            } else {
                $permissionIds = $user->permissions()->pluck('permissions.id')->all();
            }

            $user->permissions()->sync($permissionIds);
        }

        $user->load(['permissions' => fn ($permissionQuery) => $permissionQuery
            ->orderBy('group_name')
            ->orderBy('name')]);

        return response()->json([
            'message' => 'Pengguna berhasil diperbarui.',
            'data' => $this->transformUser($user),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        /** @var User|null $authUser */
        $authUser = $request->user();

        if ($authUser && $authUser->id === $user->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 422);
        }

        if ($user->normalizedRole() === 'superadmin' && $this->countSuperAdmins() <= 1) {
            return response()->json([
                'message' => 'Minimal harus ada satu superadmin aktif.',
            ], 422);
        }

        $user->permissions()->detach();
        $user->delete();

        return response()->json([
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }

    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'nama' => $user->nama ?: $user->name,
            'email' => $user->email,
            'role' => $this->displayRole((string) ($user->role_label ?: $user->role)),
            'permissions' => $user->permissions->map(fn ($permission) => [
                'id' => $permission->id,
                'code' => $permission->code,
                'name' => $permission->name,
                'group_name' => $permission->group_name,
                'description' => $permission->description,
            ])->values(),
        ];
    }

    private function normalizeRole(string $role): string
    {
        return in_array(strtolower(trim($role)), ['superadmin', 'super_admin', 'super admin'], true)
            ? 'superadmin'
            : 'admin';
    }

    private function displayRole(string $role): string
    {
        $normalized = strtolower(trim($role));

        if (in_array($normalized, ['superadmin', 'super_admin', 'super admin'], true)) {
            return 'Super Admin';
        }

        if (in_array($normalized, ['admin', 'user'], true)) {
            return 'Admin';
        }

        return trim($role);
    }

    private function countSuperAdmins(): int
    {
        return User::query()
            ->whereIn('role', ['superadmin', 'super_admin'])
            ->count();
    }

    /**
     * @return list<int>
     */
    private function defaultAdminPermissionIds(): array
    {
        $defaultCodes = PermissionCatalog::defaultAdminCodes();

        return Permission::query()
            ->whereIn('code', $defaultCodes)
            ->pluck('id')
            ->all();
    }
}
