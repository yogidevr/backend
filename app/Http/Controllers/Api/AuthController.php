<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $throttleKey = $this->throttleKey($request, $credentials['email']);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => ["Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."],
            ]);
        }

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 300);

            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak valid.'],
            ]);
        }

        RateLimiter::clear($throttleKey);
        $plainToken = $user->issueApiToken();
        $tokenHash = hash('sha256', $plainToken);
        $issuedToken = UserApiToken::query()
            ->where('token_hash', $tokenHash)
            ->first();

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($issuedToken?->expires_at)?->toIso8601String(),
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'message' => 'Data user berhasil diambil.',
            'user' => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tokenHash = (string) $request->attributes->get('api_token_hash', '');

        if ($tokenHash !== '') {
            $user->revokeApiTokenByHash($tokenHash);
        }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->fill([
            'nama' => $validated['nama'],
            'name' => $validated['nama'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
            $user->revokeAllApiTokens();
        }

        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    /**
     * @return array{id:int, nama:string, email:string, role:string}
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'nama' => $user->nama ?? $user->name ?? '',
            'email' => $user->email,
            'role' => $this->normalizeRole((string) $user->role),
        ];
    }

    private function normalizeRole(string $role): string
    {
        return match (strtolower(trim($role))) {
            'superadmin', 'super_admin' => 'superadmin',
            'user', 'admin' => 'admin',
            default => 'admin',
        };
    }

    private function throttleKey(Request $request, string $email): string
    {
        return Str::lower($email).'|'.$request->ip();
    }
}
