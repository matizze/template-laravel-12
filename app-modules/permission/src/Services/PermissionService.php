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
     * @return array<int, string>
     */
    public function flatten(array $permissions, string $prefix = ''): array
    {
        $result = [];

        foreach ($permissions as $key => $value) {
            $current = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                if (array_is_list($value)) {
                    foreach ($value as $action) {
                        $result[] = "{$current}.{$action}";
                    }
                } else {
                    $result = array_merge($result, $this->flatten($value, $current));
                }
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
