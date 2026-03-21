<?php

namespace Modules\Workspace\Database\Factories;

use Modules\User\Models\User;
use Modules\Workspace\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

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
}
