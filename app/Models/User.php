<?php

namespace App\Models;

use App\Support\PermissionCatalog;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'name',
        'email',
        'password',
        'role',
        'role_label',
        'api_token',
        'api_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'api_token',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'api_token_expires_at' => 'datetime',
        ];
    }

    public function issueApiToken(): string
    {
        $plainToken = Str::random(60);

        $this->forceFill([
            'api_token' => hash('sha256', $plainToken),
            'api_token_expires_at' => now()->addHours(8),
        ])->save();

        return $plainToken;
    }

    public function revokeApiToken(): void
    {
        $this->forceFill([
            'api_token' => null,
            'api_token_expires_at' => null,
        ])->save();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withTimestamps();
    }

    public function hasRole(string $role): bool
    {
        return $this->normalizedRole() === strtolower(trim($role));
    }

    public function isSuperAdmin(): bool
    {
        return $this->normalizedRole() === 'superadmin';
    }

    public function hasPermission(string $permissionCode): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->permissions()->where('code', $permissionCode)->exists()) {
            return true;
        }

        $legacyFallbackCodes = PermissionCatalog::legacyFallback($permissionCode);
        if ($legacyFallbackCodes === []) {
            return false;
        }

        return $this->permissions()
            ->whereIn('code', $legacyFallbackCodes)
            ->exists();
    }

    public function normalizedRole(): string
    {
        return match (strtolower(trim((string) $this->role))) {
            'superadmin', 'super_admin' => 'superadmin',
            default => 'admin',
        };
    }
}
