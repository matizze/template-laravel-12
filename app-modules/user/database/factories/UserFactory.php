<?php

declare(strict_types=1);

namespace Modules\User\Database\Factories;

use App\Enums\RoleName;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Permission\Services\RoleAssigner;
use Modules\User\Models\User;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $this->attachGlobalRole($user, RoleName::Admin, RoleSeeder::adminPermissions()));
    }

    public function superadmin(): static
    {
        return $this->afterCreating(fn (User $user) => $this->attachGlobalRole($user, RoleName::Superadmin, RoleSeeder::superadminPermissions()));
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * @param  array<string, array<int, string>>  $permissions
     */
    private function attachGlobalRole(User $user, RoleName $name, array $permissions): void
    {
        $assigner = app(RoleAssigner::class);
        $assigner->ensure($name, $permissions);
        $assigner->assign($user, $name);
    }
}
