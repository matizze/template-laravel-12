<?php

declare(strict_types=1);

namespace Modules\Permission\Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Modules\Permission\Models\Role;
use Modules\Permission\Support\RolePresets;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleName::cases() as $name) {
            Role::updateOrCreate(
                ['name' => $name->value, 'tenant_id' => null],
                ['permissions' => RolePresets::permissionsFor($name)],
            );
        }
    }
}
