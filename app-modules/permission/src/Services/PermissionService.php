<?php

namespace Modules\Permission\Services;

use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

class PermissionService
{
    public function check(User $user, string $permission): bool
    {
        return in_array($permission, $this->getUserPermissions($user), true);
    }

    /**
     * @return array<int, string>
     */
    public function getUserPermissions(User $user): array
    {
        return $this->buildPermissions($user);
    }

    /**
     * Permissions JSON is a flat map of "namespace" => list of action names.
     * The namespace itself can contain dots to express depth (e.g. "tenants.settings").
     * Each combination produces a dotted ability string: "namespace.action".
     *
     * @param  array<string, array<int, string>>  $permissions
     * @return array<int, string>
     */
    public function flatten(array $permissions): array
    {
        $result = [];

        foreach ($permissions as $namespace => $actions) {
            if (! is_array($actions)) {
                continue;
            }

            foreach ($actions as $action) {
                $result[] = "{$namespace}.{$action}";
            }
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    protected function buildPermissions(User $user): array
    {
        $all = [];

        foreach ($user->roles as $role) {
            if ($role->tenant_id !== null && $role->tenant_id !== $this->resolveTenantId()) {
                continue;
            }

            if ($role->permissions) {
                $all = array_merge($all, $this->flatten($role->permissions));
            }
        }

        return array_values(array_unique($all));
    }

    protected function resolveTenantId(): ?int
    {
        return Tenant::current()?->id;
    }
}
