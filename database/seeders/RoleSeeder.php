<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => 'superadmin', 'tenant_id' => null],
            ['permissions' => self::superadminPermissions()],
        );

        Role::updateOrCreate(
            ['name' => 'admin', 'tenant_id' => null],
            ['permissions' => self::adminPermissions()],
        );

        Role::updateOrCreate(
            ['name' => 'member', 'tenant_id' => null],
            ['permissions' => []],
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function superadminPermissions(): array
    {
        return [
            'users' => ['create', 'update', 'delete'],
            'tenants' => ['create', 'view'],
            'tenants.settings' => ['view', 'update', 'delete'],
            'tenants.users' => ['view', 'attach', 'detach'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function adminPermissions(): array
    {
        return [
            'users' => ['create', 'update', 'delete'],
        ];
    }
}
