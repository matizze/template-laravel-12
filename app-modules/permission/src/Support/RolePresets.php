<?php

declare(strict_types=1);

namespace Modules\Permission\Support;

use App\Enums\RoleName;

/**
 * Single source of truth for the default permission tree of every named
 * global role. Consumed by the seeder, the assigner service, and any other
 * Permission-module class that needs the canonical defaults.
 */
final class RolePresets
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function permissionsFor(RoleName $name): array
    {
        return match ($name) {
            RoleName::Superadmin => [
                'users' => ['create', 'update', 'delete'],
                'tenants' => ['create', 'view'],
                'tenants.settings' => ['view', 'update', 'delete'],
                'tenants.users' => ['view', 'attach', 'detach'],
            ],
            RoleName::Admin => [
                'users' => ['create', 'update', 'delete'],
            ],
            RoleName::Member => [],
        };
    }
}
