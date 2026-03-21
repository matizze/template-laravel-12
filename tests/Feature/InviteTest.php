<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\Member;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceInviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InviteTest extends TestCase
{
    use RefreshDatabase;

    private function createWorkspaceWithOwner(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create([
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
        ]);

        return [$owner, $workspace];
    }

    private function setCurrentWorkspace(Workspace $workspace): void
    {
        session(['current_workspace_id' => $workspace->id]);
    }

    // T037: test_owner_can_invite_member
    public function test_owner_can_invite_member(): void
    {
        Notification::fake();

        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $response = $this->actingAs($owner)
            ->post(route('workspace.members.invite', $workspace), [
                'email' => 'invitee@example.com',
                'role' => WorkspaceRole::Member->value,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('invitations', [
            'workspace_id' => $workspace->id,
            'email' => 'invitee@example.com',
            'role' => WorkspaceRole::Member->value,
        ]);
    }

    // T038: test_admin_can_invite_member
    public function test_admin_can_invite_member(): void
    {
        Notification::fake();

        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $admin = User::factory()->create();
        Member::factory()->admin()->create([
            'user_id' => $admin->id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('workspace.members.invite', $workspace), [
                'email' => 'invitee@example.com',
                'role' => WorkspaceRole::Member->value,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('invitations', [
            'workspace_id' => $workspace->id,
            'email' => 'invitee@example.com',
            'role' => WorkspaceRole::Member->value,
        ]);
    }

    // T039: test_member_cannot_invite_member
    public function test_member_cannot_invite_member(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $member = User::factory()->create();
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($member)
            ->post(route('workspace.members.invite', $workspace), [
                'email' => 'invitee@example.com',
                'role' => WorkspaceRole::Member->value,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('invitations', [
            'email' => 'invitee@example.com',
        ]);
    }

    // T040: test_invitation_is_created_and_email_sent
    public function test_invitation_is_created_and_email_sent(): void
    {
        Notification::fake();

        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $this->actingAs($owner)
            ->post(route('workspace.members.invite', $workspace), [
                'email' => 'invitee@example.com',
                'role' => WorkspaceRole::Member->value,
            ]);

        $this->assertDatabaseHas('invitations', [
            'workspace_id' => $workspace->id,
            'email' => 'invitee@example.com',
        ]);

        Notification::assertSentTo(
            $invitee,
            WorkspaceInviteNotification::class,
        );
    }

    // T041: test_duplicate_invitation_prevented_for_same_email
    public function test_duplicate_invitation_prevented_for_same_email(): void
    {
        Notification::fake();

        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'invitee@example.com',
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)
            ->post(route('workspace.members.invite', $workspace), [
                'email' => 'invitee@example.com',
                'role' => WorkspaceRole::Member->value,
            ]);

        $response->assertSessionHasErrors('email');
    }

    // T042: test_invitation_prevented_for_existing_member
    public function test_invitation_prevented_for_existing_member(): void
    {
        Notification::fake();

        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $existingMember = User::factory()->create(['email' => 'existing@example.com']);
        Member::factory()->create([
            'user_id' => $existingMember->id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($owner)
            ->post(route('workspace.members.invite', $workspace), [
                'email' => 'existing@example.com',
                'role' => WorkspaceRole::Member->value,
            ]);

        $response->assertSessionHasErrors('email');
    }

    // T043: test_invitee_can_accept_invitation
    public function test_invitee_can_accept_invitation(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $invitation = Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'invitee@example.com',
            'user_id' => $owner->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($invitee)
            ->get(route('invitation.accept', $invitation->token));

        $response->assertRedirect();

        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
    }

    // T044: test_invitation_acceptance_creates_membership
    public function test_invitation_acceptance_creates_membership(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $invitation = Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'invitee@example.com',
            'user_id' => $owner->id,
            'role' => WorkspaceRole::Member,
        ]);

        $this->actingAs($invitee)
            ->get(route('invitation.accept', $invitation->token));

        $this->assertDatabaseHas('members', [
            'user_id' => $invitee->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member->value,
        ]);
    }

    // T045: test_owner_can_view_members_list
    public function test_owner_can_view_members_list(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $response = $this->actingAs($owner)
            ->get(route('workspace.members.index', $workspace));

        $response->assertStatus(200);
    }

    // T046: test_members_list_shows_pending_invitations
    public function test_members_list_shows_pending_invitations(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'pending@example.com',
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)
            ->get(route('workspace.members.index', $workspace));

        $response->assertStatus(200);
        $response->assertSee('pending@example.com');
    }

    // T047: test_owner_can_change_member_role
    public function test_owner_can_change_member_role(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $memberUser = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $memberUser->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($owner)
            ->patch(route('workspace.members.updateRole', [$workspace, $member]), [
                'role' => WorkspaceRole::Admin->value,
            ]);

        $response->assertRedirect();

        $member->refresh();
        $this->assertEquals(WorkspaceRole::Admin, $member->role);
    }

    // T048: test_owner_can_remove_member
    public function test_owner_can_remove_member(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $memberUser = User::factory()->create();
        $member = Member::factory()->create([
            'user_id' => $memberUser->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($owner)
            ->delete(route('workspace.members.remove', [$workspace, $member]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('members', [
            'id' => $member->id,
        ]);
    }

    // T049: test_viewer_cannot_manage_members
    public function test_viewer_cannot_manage_members(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $viewer = User::factory()->create();
        Member::factory()->viewer()->create([
            'user_id' => $viewer->id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($viewer)
            ->post(route('workspace.members.invite', $workspace), [
                'email' => 'someone@example.com',
                'role' => WorkspaceRole::Member->value,
            ]);

        $response->assertStatus(403);
    }

    // T078: test_non_owner_member_can_leave_workspace
    public function test_non_owner_member_can_leave_workspace(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $member = User::factory()->create();
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($member)
            ->post(route('workspace.members.leave', $workspace));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('members', [
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
        ]);
    }

    // T079: test_owner_cannot_leave_workspace
    public function test_owner_cannot_leave_workspace(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $response = $this->actingAs($owner)
            ->post(route('workspace.members.leave', $workspace));

        $response->assertRedirect();

        $this->assertDatabaseHas('members', [
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Owner->value,
        ]);
    }

    // T080: test_leaving_workspace_removes_membership
    public function test_leaving_workspace_removes_membership(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $member = User::factory()->create();
        $membership = Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $this->actingAs($member)
            ->post(route('workspace.members.leave', $workspace));

        $this->assertDatabaseMissing('members', [
            'id' => $membership->id,
        ]);
    }

    // T081: test_leaving_workspace_revokes_access
    public function test_leaving_workspace_revokes_access(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $member = User::factory()->create();
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $this->actingAs($member)
            ->post(route('workspace.members.leave', $workspace));

        // After leaving, member should not be able to access workspace members list
        $response = $this->actingAs($member)
            ->get(route('workspace.members.index', $workspace));

        $response->assertStatus(403);
    }

    // T082: test_member_with_projects_can_leave
    public function test_member_with_projects_can_leave(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $member = User::factory()->create();
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        // Create a project belonging to this member in the workspace
        \App\Models\Project::create([
            'name' => 'Member Project',
            'description' => 'Test project',
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($member)
            ->post(route('workspace.members.leave', $workspace));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('members', [
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
        ]);

        // Project still exists
        $this->assertDatabaseHas('projects', [
            'name' => 'Member Project',
            'workspace_id' => $workspace->id,
        ]);
    }
}
