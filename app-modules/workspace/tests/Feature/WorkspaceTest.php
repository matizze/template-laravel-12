<?php

namespace Tests\Feature;

use Modules\Workspace\Enums\WorkspaceRole;
use Modules\Workspace\Models\Invitation;
use Modules\Workspace\Models\Member;
use Modules\User\Models\User;
use Modules\Workspace\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_workspace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('workspace.store'), [
                'name' => 'Meu Workspace',
                'description' => 'Descrição do workspace',
            ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('workspaces', [
            'name' => 'Meu Workspace',
            'slug' => 'meu-workspace',
            'user_id' => $user->id,
            'description' => 'Descrição do workspace',
        ]);

        $this->assertDatabaseHas('members', [
            'user_id' => $user->id,
            'role' => WorkspaceRole::Owner->value,
        ]);
    }

    public function test_workspace_slug_is_generated_if_not_provided(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('workspace.store'), [
                'name' => 'Novo Projeto Legal',
            ]);

        $this->assertDatabaseHas('workspaces', [
            'slug' => 'novo-projeto-legal',
        ]);
    }

    public function test_workspace_creation_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('workspace.store'), [
                'description' => 'Apenas descrição',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_workspace_slug_must_be_unique(): void
    {
        $user = User::factory()->create();

        Workspace::factory()->create(['slug' => 'meu-workspace', 'user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('workspace.store'), [
                'name' => 'Meu Workspace',
                'slug' => 'meu-workspace',
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_workspace_name_has_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('workspace.store'), [
                'name' => str_repeat('a', 256),
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_is_switched_to_new_workspace_after_creation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('workspace.store'), [
                'name' => 'Workspace Novo',
            ]);

        $workspace = Workspace::where('name', 'Workspace Novo')->first();

        $this->assertEquals($workspace->id, session('current_workspace_id'));
    }

    public function test_guest_cannot_create_workspace(): void
    {
        $response = $this->post(route('workspace.store'), [
            'name' => 'Workspace',
        ]);

        $response->assertRedirect('/auth/login');
    }

    public function test_user_can_switch_between_workspaces(): void
    {
        $user = User::factory()->create();
        $workspace1 = Workspace::factory()->create(['user_id' => $user->id]);
        $workspace2 = Workspace::factory()->create(['user_id' => $user->id]);

        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace1->id]);
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace2->id]);

        $response = $this->actingAs($user)
            ->post(route('workspace.switch', $workspace2));

        $response->assertRedirect();
        $this->assertEquals($workspace2->id, session('current_workspace_id'));
    }

    public function test_owner_can_update_workspace_name(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $response = $this->actingAs($user)
            ->patch(route('workspace.settings.update', $workspace), [
                'name' => 'Novo Nome',
                'slug' => $workspace->slug,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Novo Nome',
        ]);
    }

    public function test_owner_can_update_workspace_description(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $response = $this->actingAs($user)
            ->patch(route('workspace.settings.update', $workspace), [
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'description' => 'Nova descrição do workspace',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'description' => 'Nova descrição do workspace',
        ]);
    }

    public function test_workspace_slug_validation_on_update(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        Workspace::factory()->create(['slug' => 'slug-existente']);

        $response = $this->actingAs($user)
            ->patch(route('workspace.settings.update', $workspace), [
                'name' => $workspace->name,
                'slug' => 'slug-existente',
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_member_cannot_update_workspace(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $member = User::factory()->create();
        Member::factory()->create(['user_id' => $member->id, 'workspace_id' => $workspace->id, 'role' => WorkspaceRole::Member]);

        $response = $this->actingAs($member)
            ->patch(route('workspace.settings.update', $workspace), [
                'name' => 'Tentativa de Alteração',
                'slug' => $workspace->slug,
            ]);

        $response->assertForbidden();
    }

    public function test_owner_can_delete_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $response = $this->actingAs($user)
            ->delete(route('workspace.destroy', $workspace));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('workspaces', [
            'id' => $workspace->id,
        ]);
    }

    public function test_member_cannot_delete_workspace(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $member = User::factory()->create();
        Member::factory()->create(['user_id' => $member->id, 'workspace_id' => $workspace->id, 'role' => WorkspaceRole::Member]);

        $response = $this->actingAs($member)
            ->delete(route('workspace.destroy', $workspace));

        $response->assertForbidden();

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
        ]);
    }

    public function test_workspace_deletion_removes_associated_data(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $otherMember = User::factory()->create();
        Member::factory()->create(['user_id' => $otherMember->id, 'workspace_id' => $workspace->id, 'role' => WorkspaceRole::Member]);

        Invitation::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('workspace.destroy', $workspace));

        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
        $this->assertDatabaseMissing('members', ['workspace_id' => $workspace->id]);
        $this->assertDatabaseMissing('invitations', ['workspace_id' => $workspace->id]);
    }


    public function test_owner_can_transfer_ownership(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $newOwner = User::factory()->create();
        Member::factory()->create([
            'user_id' => $newOwner->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($owner)
            ->post(route('workspace.transferOwnership', $workspace), [
                'user_id' => $newOwner->id,
            ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('members', [
            'user_id' => $newOwner->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Owner->value,
        ]);
    }

    public function test_ownership_transfer_demotes_old_owner(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $newOwner = User::factory()->create();
        Member::factory()->create([
            'user_id' => $newOwner->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $this->actingAs($owner)
            ->post(route('workspace.transferOwnership', $workspace), [
                'user_id' => $newOwner->id,
            ]);

        $this->assertDatabaseHas('members', [
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Admin->value,
        ]);
    }

    public function test_ownership_transfer_promotes_new_owner(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $newOwner = User::factory()->create();
        Member::factory()->create([
            'user_id' => $newOwner->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Admin,
        ]);

        $this->actingAs($owner)
            ->post(route('workspace.transferOwnership', $workspace), [
                'user_id' => $newOwner->id,
            ]);

        $this->assertDatabaseHas('members', [
            'user_id' => $newOwner->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Owner->value,
        ]);

        $workspace->refresh();
        $this->assertEquals($newOwner->id, $workspace->user_id);
    }

    public function test_cannot_transfer_to_non_member(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $nonMember = User::factory()->create();

        $response = $this->actingAs($owner)
            ->post(route('workspace.transferOwnership', $workspace), [
                'user_id' => $nonMember->id,
            ]);

        $response->assertSessionHasErrors('user_id');

        $this->assertDatabaseHas('members', [
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Owner->value,
        ]);
    }

    public function test_admin_cannot_transfer_ownership(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $admin = User::factory()->create();
        Member::factory()->admin()->create([
            'user_id' => $admin->id,
            'workspace_id' => $workspace->id,
        ]);

        $anotherMember = User::factory()->create();
        Member::factory()->create([
            'user_id' => $anotherMember->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('workspace.transferOwnership', $workspace), [
                'user_id' => $anotherMember->id,
            ]);

        $response->assertForbidden();
    }

    public function test_member_cannot_transfer_ownership(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $member = User::factory()->create();
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($member)
            ->post(route('workspace.transferOwnership', $workspace), [
                'user_id' => $owner->id,
            ]);

        $response->assertForbidden();
    }

    public function test_owner_can_update_member_role_via_patch(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $member = User::factory()->create();
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($owner)
            ->patch(route('workspace.members.updateRole', [$workspace, $member]), [
                'role' => WorkspaceRole::Admin->value,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('members', [
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Admin->value,
        ]);
    }

    public function test_admin_can_update_member_role_via_patch(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $admin = User::factory()->create();
        Member::factory()->admin()->create([
            'user_id' => $admin->id,
            'workspace_id' => $workspace->id,
        ]);

        $member = User::factory()->create();
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('workspace.members.updateRole', [$workspace, $member]), [
                'role' => WorkspaceRole::Viewer->value,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('members', [
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Viewer->value,
        ]);
    }

    public function test_member_without_permission_receives_403_on_role_update(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $member = User::factory()->create();
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $anotherMember = User::factory()->create();
        Member::factory()->create([
            'user_id' => $anotherMember->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($member)
            ->patch(route('workspace.members.updateRole', [$workspace, $anotherMember]), [
                'role' => WorkspaceRole::Admin->value,
            ]);

        $response->assertForbidden();
    }

    public function test_put_request_to_update_role_returns_405(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $owner->id]);
        Member::factory()->owner()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id]);

        $member = User::factory()->create();
        Member::factory()->create([
            'user_id' => $member->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $response = $this->actingAs($owner)
            ->put(route('workspace.members.updateRole', [$workspace, $member]), [
                'role' => WorkspaceRole::Admin->value,
            ]);

        $response->assertStatus(405);
    }
}
