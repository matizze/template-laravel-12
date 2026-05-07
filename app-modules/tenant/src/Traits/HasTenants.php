<?php

namespace Modules\Tenant\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;

trait HasTenants
{
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->using(TenantUser::class)
            ->withPivot('id')
            ->withTimestamps();
    }

    public function isMemberOf(Tenant $tenant): bool
    {
        return $tenant->tenantUsers()
            ->where('user_id', $this->id)
            ->exists();
    }
}
