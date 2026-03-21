<?php

namespace Tests\Concerns;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;

trait CreatesWorkspace
{
    /**
     * @return array{User, Workspace}
     */
    private function createOwnerWithWorkspace(string $name = 'My Workspace'): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->for($user, 'owner')->create(['name' => $name]);
        $user->workspaces()->attach($workspace, ['role' => WorkspaceRole::Owner]);

        return [$user, $workspace];
    }

    /**
     * @return array{User, User, Workspace}
     */
    private function createOwnerAndMemberWithWorkspace(string $name = 'My Workspace'): array
    {
        [$owner, $workspace] = $this->createOwnerWithWorkspace($name);
        $member = User::factory()->create();
        $member->workspaces()->attach($workspace, ['role' => WorkspaceRole::Member]);

        return [$owner, $member, $workspace];
    }
}
