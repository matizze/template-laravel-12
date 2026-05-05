<?php

namespace Modules\Tenant\Policies;

use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class TenantPolicy
{
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->roleIn($tenant) !== null;
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->roleIn($tenant) === TenantRole::Owner;
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        if ($user->roleIn($tenant) === TenantRole::Owner) {
            return true;
        }

        return $tenant->getAttribute('user_id') === $user->getAttribute('id');
    }

    public function manageTenantUsers(User $user, Tenant $tenant): bool
    {
        $role = $user->roleIn($tenant);

        return in_array($role, [TenantRole::Owner, TenantRole::Admin]);
    }

    public function transferOwnership(User $user, Tenant $tenant): bool
    {
        return $user->roleIn($tenant) === TenantRole::Owner;
    }

    /**
     * Restore a soft-deleted tenant. Only the original creator (`tenant.user_id`)
     * may restore — `roleIn` cannot be used because trashed tenants don't
     * appear in pivot relationships.
     */
    public function restore(User $user, Tenant $tenant): bool
    {
        return $tenant->getAttribute('user_id') === $user->getAttribute('id');
    }
}
