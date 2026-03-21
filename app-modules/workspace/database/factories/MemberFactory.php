<?php

namespace Modules\Workspace\Database\Factories;

use Modules\Workspace\Enums\WorkspaceRole;
use Modules\Workspace\Models\Member;
use Modules\User\Models\User;
use Modules\Workspace\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'workspace_id' => Workspace::factory(),
            'role' => WorkspaceRole::Member,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => WorkspaceRole::Owner,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => WorkspaceRole::Admin,
        ]);
    }

    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => WorkspaceRole::Viewer,
        ]);
    }
}
