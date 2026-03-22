<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Modules\Workspace\Models\Member;
use Modules\Workspace\Models\Workspace;
use Tests\TestCase;

class UserWorkspaceRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_dynamic_workspaces_relation(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        Member::factory()->owner()->create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        $workspaces = $user->workspaces;

        $this->assertCount(1, $workspaces);
        $this->assertTrue($workspaces->first()->is($workspace));
    }

    public function test_user_workspaces_relation_returns_empty_without_memberships(): void
    {
        $user = User::factory()->create();

        $this->assertCount(0, $user->workspaces);
    }

    public function test_user_workspaces_relation_excludes_non_member_workspaces(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $otherUser->id]);
        Member::factory()->owner()->create([
            'user_id' => $otherUser->id,
            'workspace_id' => $workspace->id,
        ]);

        $this->assertCount(0, $user->workspaces);
    }
}
