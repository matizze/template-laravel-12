<?php

namespace Modules\Tenant\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Models\User;

/**
 * @extends Factory<TenantUser>
 */
class TenantUserFactory extends Factory
{
    protected $model = TenantUser::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tenant_id' => Tenant::factory(),
            'role' => TenantRole::Member,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TenantRole::Owner,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TenantRole::Admin,
        ]);
    }

    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TenantRole::Viewer,
        ]);
    }
}
