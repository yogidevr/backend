<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $permissions = PermissionCatalog::all();

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission['code']],
                [
                    'name' => $permission['name'],
                    'group_name' => $permission['group_name'],
                    'description' => $permission['name'],
                ]
            );
        }

        // Cleanup legacy/unused permissions to keep matrix clean.
        Permission::query()->whereIn('code', ['delete.data', 'export.pdf'])->delete();

        $this->migrateLegacyAssignments();
    }

    private function migrateLegacyAssignments(): void
    {
        $legacyCodes = PermissionCatalog::legacyCodes();

        $users = User::query()
            ->with('permissions:id,code')
            ->whereHas('permissions', fn ($query) => $query->whereIn('code', $legacyCodes))
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            if ($user->isSuperAdmin()) {
                continue;
            }

            $currentCodes = $user->permissions
                ->pluck('code')
                ->map(static fn ($code) => (string) $code)
                ->all();

            $nextCodes = [];
            foreach ($currentCodes as $code) {
                if (!in_array($code, $legacyCodes, true)) {
                    $nextCodes[] = $code;
                    continue;
                }

                $nextCodes = array_merge($nextCodes, PermissionCatalog::expandLegacyCode($code));
            }

            $nextCodes = array_values(array_unique($nextCodes));
            $nextPermissionIds = Permission::query()
                ->whereIn('code', $nextCodes)
                ->pluck('id')
                ->all();

            $user->permissions()->sync($nextPermissionIds);
        }
    }
}
