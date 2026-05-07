<?php

namespace Modules\User\Database\Factories;

use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(
                ['name' => 'admin', 'tenant_id' => null],
                ['permissions' => RoleSeeder::adminPermissions()],
            );
            $role->assign($user);
        });
    }

    public function superadmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(
                ['name' => 'superadmin', 'tenant_id' => null],
                ['permissions' => RoleSeeder::superadminPermissions()],
            );
            $role->assign($user);
        });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
