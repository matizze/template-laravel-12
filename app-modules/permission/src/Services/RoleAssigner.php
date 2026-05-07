<?php

declare(strict_types=1);

namespace Modules\Permission\Services;

use App\Enums\RoleName;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

/**
 * Bridge service so consumers (User module, factories, console commands) can
 * attach and rotate global roles without importing the Role model directly.
 */
final class RoleAssigner
{
    /**
     * Attach the global role identified by $name to the given user.
     */
    public function assign(User $user, RoleName $name): void
    {
        $this->resolve($name)->assign($user);
    }

    /**
     * Replace every global role on the user with $name.
     */
    public function replaceGlobal(User $user, RoleName $name): void
    {
        $user->roles()->whereNull('tenant_id')->detach();

        $this->assign($user, $name);
    }

    /**
     * Ensure the global role exists with the given permission tree, creating
     * it if necessary. Used by factories and CLI commands that may run before
     * the seeder.
     *
     * @param  array<string, array<int, string>>  $permissions
     */
    public function ensure(RoleName $name, array $permissions = []): void
    {
        Role::firstOrCreate(
            ['name' => $name->value, 'tenant_id' => null],
            ['permissions' => $permissions],
        );
    }

    private function resolve(RoleName $name): Role
    {
        return Role::where('name', $name->value)
            ->whereNull('tenant_id')
            ->firstOrFail();
    }
}
