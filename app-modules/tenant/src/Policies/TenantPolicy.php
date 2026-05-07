<?php

namespace Modules\Tenant\Policies;

use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class TenantPolicy
{
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isMemberOf($tenant);
    }

    public function update(User $user, Tenant $tenant): bool
    {
        if ($tenant->trashed()) {
            return false;
        }

        return $user->can('tenants.settings.update', $tenant);
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        if ($tenant->trashed()) {
            return false;
        }

        return $user->can('tenants.settings.delete', $tenant);
    }

    public function restore(User $user, Tenant $tenant): bool
    {
        if (! $tenant->trashed()) {
            return false;
        }

        return $user->can('tenants.settings.update', $tenant);
    }
}
