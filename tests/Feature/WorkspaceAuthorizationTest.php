<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWorkspace;
use Tests\TestCase;

class WorkspaceAuthorizationTest extends TestCase
{
    use CreatesWorkspace;
    use RefreshDatabase;

    public function test_user_cannot_access_workspace_they_are_not_member_of(): void
    {
        [$user] = $this->createOwnerWithWorkspace();
        [, $otherWorkspace] = $this->createOwnerWithWorkspace('Other Workspace');

        $response = $this->actingAs($user)->get("/workspace/{$otherWorkspace->id}/members");
        $response->assertForbidden();
    }

    public function test_owner_can_update_workspace(): void
    {
        [$owner, $workspace] = $this->createOwnerWithWorkspace();

        $response = $this->actingAs($owner)->patch("/workspace/{$workspace->id}", [
            'name' => 'Updated',
            'slug' => $workspace->slug,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('workspaces', ['name' => 'Updated']);
    }
}
