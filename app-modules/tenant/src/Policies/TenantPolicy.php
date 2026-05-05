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
        return $user->roleIn($tenant) === TenantRole::Owner;
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
}
