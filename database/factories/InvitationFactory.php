<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => null,
            'email' => $this->faker->unique()->safeEmail(),
            'role' => WorkspaceRole::Member,
            'token' => Str::random(32),
            'accepted_at' => null,
        ];
    }

    public function accepted(): self
    {
        return $this->state([
            'user_id' => User::factory(),
            'accepted_at' => now(),
        ]);
    }

    public function pending(): self
    {
        return $this->state([
            'user_id' => null,
            'accepted_at' => null,
        ]);
    }

    public function withRole(WorkspaceRole $role): self
    {
        return $this->state([
            'role' => $role,
        ]);
    }
}
