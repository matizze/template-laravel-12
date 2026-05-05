<?php

namespace Modules\Tenant\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;

trait HasTenants
{
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->using(TenantUser::class)
            ->withPivot('id', 'role')
            ->withCasts(['role' => TenantRole::class])
            ->withTimestamps();
    }

    public function ownedTenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /** @var array<int, TenantRole|null> */
    private array $roleCache = [];

    public function roleIn(Tenant $tenant): ?TenantRole
    {
        if (array_key_exists($tenant->id, $this->roleCache)) {
            return $this->roleCache[$tenant->id];
        }

        $tenantUser = $tenant->tenantUsers()
            ->where('user_id', $this->id)
            ->first();

        return $this->roleCache[$tenant->id] = $tenantUser?->role;
    }

    public function isMemberOf(Tenant $tenant): bool
    {
        return $this->roleIn($tenant) !== null;
    }
}
