<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWorkspace;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use CreatesWorkspace;
    use RefreshDatabase;

    public function test_owner_can_invite_member(): void
    {
        [$owner, $workspace] = $this->createOwnerWithWorkspace();

        $response = $this->actingAs($owner)->post("/workspace/{$workspace->id}/invite", [
            'email' => 'new@example.com',
            'role' => WorkspaceRole::Member->value,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invitations', ['email' => 'new@example.com']);
    }

    public function test_member_cannot_invite(): void
    {
        [, $member, $workspace] = $this->createOwnerAndMemberWithWorkspace();

        $response = $this->actingAs($member)->post("/workspace/{$workspace->id}/invite", [
            'email' => 'new@example.com',
            'role' => WorkspaceRole::Member->value,
        ]);

        $response->assertForbidden();
    }
}
