<?php

namespace Modules\Permission\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Permission\Models\Role;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'tenant_id' => null,
            'permissions' => [],
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin',
            'permissions' => [
                'users' => ['create', 'update', 'delete'],
            ],
        ]);
    }

    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'member',
            'permissions' => [],
        ]);
    }
}
