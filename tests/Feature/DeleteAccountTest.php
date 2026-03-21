<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Member;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    // T098: test_non_owner_can_delete_account
    public function test_non_owner_can_delete_account(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create([
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
        ]);

        $member = User::factory()->create();
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($member)
            ->delete(route('settings.account.destroy'), [
                'password' => 'password',
            ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', [
            'id' => $member->id,
        ]);
    }

    // T099: test_owner_cannot_delete_account
    public function test_owner_cannot_delete_account(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create([
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($owner)
            ->delete(route('settings.account.destroy'), [
                'password' => 'password',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
        ]);
    }

    // T100: test_deleting_account_removes_memberships
    public function test_deleting_account_removes_memberships(): void
    {
        $owner = User::factory()->create();
        $workspace1 = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create([
            'user_id' => $owner->id,
            'workspace_id' => $workspace1->id,
        ]);

        $workspace2 = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create([
            'user_id' => $owner->id,
            'workspace_id' => $workspace2->id,
        ]);

        $member = User::factory()->create();
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace1->id,
            'role' => WorkspaceRole::Member,
        ]);
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace2->id,
            'role' => WorkspaceRole::Admin,
        ]);

        $this->actingAs($member)
            ->delete(route('settings.account.destroy'), [
                'password' => 'password',
            ]);

        $this->assertDatabaseMissing('members', [
            'user_id' => $member->id,
        ]);
    }

    // T101: test_user_with_multiple_workspaces_can_delete_if_not_owner
    public function test_user_with_multiple_workspaces_can_delete_if_not_owner(): void
    {
        $owner1 = User::factory()->create();
        $workspace1 = Workspace::factory()->create(['user_id' => $owner1->id]);
        Member::factory()->owner()->create([
            'user_id' => $owner1->id,
            'workspace_id' => $workspace1->id,
        ]);

        $owner2 = User::factory()->create();
        $workspace2 = Workspace::factory()->create(['user_id' => $owner2->id]);
        Member::factory()->owner()->create([
            'user_id' => $owner2->id,
            'workspace_id' => $workspace2->id,
        ]);

        $member = User::factory()->create();
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace1->id,
            'role' => WorkspaceRole::Member,
        ]);
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace2->id,
            'role' => WorkspaceRole::Admin,
        ]);

        $response = $this->actingAs($member)
            ->delete(route('settings.account.destroy'), [
                'password' => 'password',
            ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', [
            'id' => $member->id,
        ]);

        $this->assertDatabaseMissing('members', [
            'user_id' => $member->id,
        ]);
    }

    // T102: test_deleting_account_requires_ownership_transfer
    public function test_deleting_account_requires_ownership_transfer(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create([
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($owner)
            ->delete(route('settings.account.destroy'), [
                'password' => 'password',
            ]);

        $response->assertRedirect();

        // User should still exist
        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
        ]);

        // Workspace should still exist
        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
        ]);
    }
}
