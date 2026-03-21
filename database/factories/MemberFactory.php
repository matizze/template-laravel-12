<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Models\Member;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'workspace_id' => Workspace::factory(),
            'role' => WorkspaceRole::Member,
        ];
    }

    public function owner(): self
    {
        return $this->state([
            'role' => WorkspaceRole::Owner,
        ]);
    }

    public function admin(): self
    {
        return $this->state([
            'role' => WorkspaceRole::Admin,
        ]);
    }

    public function viewer(): self
    {
        return $this->state([
            'role' => WorkspaceRole::Viewer,
        ]);
    }
}
