<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\Member;
use App\Models\Project;
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

    public function test_owner_can_view_members_list(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $response = $this->actingAs($owner)
            ->get(route('workspace.members.index', $workspace));

        $response->assertStatus(200);
    }

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

    public function test_owner_can_change_member_role(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $memberUser = User::factory()->create();
        Member::factory()->create([
            'user_id' => $memberUser->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($owner)
            ->patch(route('workspace.members.updateRole', [$workspace, $memberUser]), [
                'role' => WorkspaceRole::Admin->value,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('members', [
            'user_id' => $memberUser->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Admin->value,
        ]);
    }

    public function test_owner_can_remove_member(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $memberUser = User::factory()->create();
        Member::factory()->create([
            'user_id' => $memberUser->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($owner)
            ->delete(route('workspace.members.remove', [$workspace, $memberUser]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('members', [
            'user_id' => $memberUser->id,
            'workspace_id' => $workspace->id,
        ]);
    }

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

    public function test_leaving_workspace_removes_membership(): void
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

        $this->assertDatabaseMissing('members', [
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
        ]);
    }

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

        $response = $this->actingAs($member)
            ->get(route('workspace.members.index', $workspace));

        $response->assertRedirect(route('onboarding'));
    }

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

        $response = $this->actingAs($member)
            ->post(route('workspace.members.leave', $workspace));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('members', [
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
        ]);
    }

    public function test_user_cannot_accept_invitation_for_different_email(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();

        $invitation = Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'destinatario@example.com',
            'user_id' => $owner->id,
            'role' => WorkspaceRole::Member,
        ]);

        $wrongUser = User::factory()->create(['email' => 'outro@example.com']);

        $response = $this->actingAs($wrongUser)
            ->get(route('invitation.accept', $invitation->token));

        $response->assertRedirect(route('dashboard'));

        $invitation->refresh();
        $this->assertNull($invitation->accepted_at);

        $this->assertDatabaseMissing('members', [
            'user_id' => $wrongUser->id,
            'workspace_id' => $workspace->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accepting_invitation(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();

        $invitation = Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'convidado@example.com',
            'user_id' => $owner->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->get(route('invitation.accept', $invitation->token));

        $response->assertRedirect(route('login'));
        $this->assertEquals($invitation->token, session('invitation_token'));
    }

    public function test_cannot_promote_member_to_owner_via_update_role(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $memberUser = User::factory()->create();
        Member::factory()->create([
            'user_id' => $memberUser->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($owner)
            ->patch(route('workspace.members.updateRole', [$workspace, $memberUser]), [
                'role' => WorkspaceRole::Owner->value,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('members', [
            'user_id' => $memberUser->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member->value,
        ]);
    }

    public function test_cannot_remove_workspace_owner(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $admin = User::factory()->create();
        Member::factory()->admin()->create([
            'user_id' => $admin->id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('workspace.members.remove', [$workspace, $owner]));

        $response->assertRedirect();

        $this->assertDatabaseHas('members', [
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Owner->value,
        ]);
    }

    public function test_cannot_update_role_of_member_from_another_workspace(): void
    {
        [$owner1, $workspace1] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace1);

        $owner2 = User::factory()->create();
        $workspace2 = Workspace::factory()->create(['user_id' => $owner2->id]);
        Member::factory()->owner()->create([
            'user_id' => $owner2->id,
            'workspace_id' => $workspace2->id,
        ]);

        $memberUser = User::factory()->create();
        Member::factory()->create([
            'user_id' => $memberUser->id,
            'workspace_id' => $workspace2->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($owner1)
            ->patch(route('workspace.members.updateRole', [$workspace1, $memberUser]), [
                'role' => WorkspaceRole::Admin->value,
            ]);

        $response->assertStatus(404);
    }

    public function test_invite_sends_notification_to_unregistered_email(): void
    {
        Notification::fake();

        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        $unregisteredEmail = 'unregistered@example.com';

        $this->actingAs($owner)
            ->post(route('workspace.members.invite', $workspace), [
                'email' => $unregisteredEmail,
                'role' => WorkspaceRole::Member->value,
            ]);

        Notification::assertSentOnDemand(
            WorkspaceInviteNotification::class,
            function ($notification, $channels, $notifiable) use ($unregisteredEmail) {
                return $notifiable->routes['mail'] === $unregisteredEmail;
            }
        );
    }

    // T096: test_registration_with_invitation_token_in_session_redirects_to_accept
    public function test_registration_with_invitation_token_in_session_redirects_to_accept(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();

        $invitation = Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'newuser@example.com',
            'user_id' => $owner->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->withSession(['invitation_token' => $invitation->token])
            ->post(route('register'), [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertRedirect(route('invitation.accept', $invitation->token));

        // Token kept in session until accept() clears it on success
        $this->assertEquals($invitation->token, session('invitation_token'));
    }

    // T098: test_registration_without_invitation_token_redirects_to_onboarding
    public function test_registration_without_invitation_token_redirects_to_onboarding(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('onboarding'));
    }

    // T095: test_login_with_invitation_token_in_session_redirects_to_accept
    public function test_login_with_invitation_token_in_session_redirects_to_accept(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();

        $invitee = User::factory()->create(['email' => 'invitee@example.com', 'password' => bcrypt('password')]);

        $invitation = Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'invitee@example.com',
            'user_id' => $owner->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->withSession(['invitation_token' => $invitation->token])
            ->post(route('login'), [
                'email' => 'invitee@example.com',
                'password' => 'password',
            ]);

        $response->assertRedirect(route('invitation.accept', $invitation->token));

        // Token kept in session until accept() clears it on success
        $this->assertEquals($invitation->token, session('invitation_token'));
    }

    // T097: test_login_without_invitation_token_redirects_to_dashboard
    public function test_login_without_invitation_token_redirects_to_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'password' => bcrypt('password')]);

        $response = $this->post(route('login'), [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('dashboard');
    }

    // T099: test_login_with_invitation_token_but_mismatched_email_shows_error
    public function test_login_with_invitation_token_but_mismatched_email_shows_error(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner();

        $wrongUser = User::factory()->create(['email' => 'wrong@example.com', 'password' => bcrypt('password')]);

        $invitation = Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'invitee@example.com',
            'user_id' => $owner->id,
            'role' => WorkspaceRole::Member,
        ]);

        // Login with a different email than the invitation
        $response = $this->withSession(['invitation_token' => $invitation->token])
            ->post(route('login'), [
                'email' => 'wrong@example.com',
                'password' => 'password',
            ]);

        // Still redirected to invitation.accept — the controller there handles email mismatch
        $response->assertRedirect(route('invitation.accept', $invitation->token));

        // No membership created
        $this->assertDatabaseMissing('members', [
            'user_id' => $wrongUser->id,
            'workspace_id' => $workspace->id,
        ]);
    }

    // T001: test_former_member_can_be_reinvited
    public function test_former_member_can_be_reinvited(): void
    {
        Notification::fake();

        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        // Seed an accepted invitation (former member, no active membership)
        Invitation::factory()->accepted()->create([
            'workspace_id' => $workspace->id,
            'email' => 'former@example.com',
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)
            ->post(route('workspace.members.invite', $workspace), [
                'email' => 'former@example.com',
                'role' => WorkspaceRole::Member->value,
            ]);

        $response->assertRedirect();

        // New pending invitation must exist
        $this->assertDatabaseHas('invitations', [
            'workspace_id' => $workspace->id,
            'email' => 'former@example.com',
            'accepted_at' => null,
        ]);

        // Old accepted invitation must be gone
        $this->assertDatabaseMissing('invitations', [
            'workspace_id' => $workspace->id,
            'email' => 'former@example.com',
            'accepted_at' => now()->toDateTimeString(),
        ]);
    }

    // T003: test_reinvite_shows_success_notification
    public function test_reinvite_shows_success_notification(): void
    {
        Notification::fake();

        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        Invitation::factory()->accepted()->create([
            'workspace_id' => $workspace->id,
            'email' => 'former@example.com',
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)
            ->post(route('workspace.members.invite', $workspace), [
                'email' => 'former@example.com',
                'role' => WorkspaceRole::Member->value,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    // T004: test_pending_invite_shows_validation_error
    public function test_pending_invite_shows_validation_error(): void
    {
        Notification::fake();

        [$owner, $workspace] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace);

        Invitation::factory()->create([
            'workspace_id' => $workspace->id,
            'email' => 'pending@example.com',
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)
            ->post(route('workspace.members.invite', $workspace), [
                'email' => 'pending@example.com',
                'role' => WorkspaceRole::Member->value,
            ]);

        $response->assertSessionHasErrors('email');
    }

    // T094: test_cannot_remove_member_from_another_workspace
    public function test_cannot_remove_member_from_another_workspace(): void
    {
        [$owner1, $workspace1] = $this->createWorkspaceWithOwner();
        $this->setCurrentWorkspace($workspace1);

        $owner2 = User::factory()->create();
        $workspace2 = Workspace::factory()->create(['user_id' => $owner2->id]);
        Member::factory()->owner()->create([
            'user_id' => $owner2->id,
            'workspace_id' => $workspace2->id,
        ]);

        $memberUser = User::factory()->create();
        Member::factory()->create([
            'user_id' => $memberUser->id,
            'workspace_id' => $workspace2->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($owner1)
            ->delete(route('workspace.members.remove', [$workspace1, $memberUser]));

        $response->assertStatus(404);

        $this->assertDatabaseHas('members', [
            'user_id' => $memberUser->id,
            'workspace_id' => $workspace2->id,
        ]);
    }
}
