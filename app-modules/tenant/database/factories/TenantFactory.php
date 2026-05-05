<?php

namespace Modules\Tenant\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'description' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }

    public function withParent(Tenant $parent): self
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }

    public function root(): self
    {
        return $this->state(fn () => ['parent_id' => null]);
    }
}
