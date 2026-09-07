<?php

namespace App\Models;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements CanResetPassword
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /* ---------------- Relationships ---------------- */

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnOrder::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /* ---------------- RBAC & Role Helpers ---------------- */

    public function hasRole(string|array $roles): bool
    {
        $roleList = is_array($roles) ? $roles : func_get_args();

        if ($this->roles()->whereIn('slug', $roleList)->exists()) {
            return true;
        }

        if ($this->roles()->whereIn('slug', ['admin', 'super-admin'])->exists()) {
            return true;
        }

        return false;
    }

    public function hasExactRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasExactRole('admin') || $this->hasExactRole('super-admin');
    }

    public function isManager(): bool
    {
        return $this->hasExactRole('manager');
    }

    public function isCashier(): bool
    {
        return $this->hasExactRole('cashier');
    }

    public function isStaff(): bool
    {
        return $this->hasExactRole('staff');
    }

    public function primaryRoleName(): string
    {
        return $this->roles()->first()?->name ?? 'User';
    }

    public function hasPermission(string|array $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $permissions = is_array($permission) ? $permission : func_get_args();

        return $this->roles()->whereHas('permissions', function ($query) use ($permissions) {
            $query->whereIn('slug', $permissions)
                  ->orWhereIn('module', $permissions);
        })->exists();
    }

    /* ---------------- Query Scopes ---------------- */

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    public function scopeRoleFilter($query, $roleId)
    {
        if (blank($roleId) || $roleId === 'all') {
            return $query;
        }

        return $query->whereHas('roles', function ($q) use ($roleId) {
            if (is_numeric($roleId)) {
                $q->where('roles.id', $roleId);
            } else {
                $q->where('roles.slug', $roleId);
            }
        });
    }

    public function scopeStatusFilter($query, ?string $status)
    {
        if (blank($status) || $status === 'all') {
            return $query;
        }

        if ($status === 'active') {
            return $query->where('is_active', true);
        }

        if ($status === 'inactive' || $status === 'deactivated') {
            return $query->where('is_active', false);
        }

        return $query;
    }
}

