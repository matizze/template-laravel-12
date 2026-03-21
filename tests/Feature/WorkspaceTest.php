<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    // T018: test_user_can_create_workspace
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

        // Verifica que o criador é membro com role owner
        $this->assertDatabaseHas('members', [
            'user_id' => $user->id,
            'role' => WorkspaceRole::Owner->value,
        ]);
    }

    // T019: test_workspace_slug_is_generated_if_not_provided
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

    // T020: test_workspace_creation_requires_name
    public function test_workspace_creation_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('workspace.store'), [
                'description' => 'Apenas descrição',
            ]);

        $response->assertSessionHasErrors('name');
    }

    // T021: test_workspace_slug_must_be_unique
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

    // T022: test_workspace_name_has_max_length
    public function test_workspace_name_has_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('workspace.store'), [
                'name' => str_repeat('a', 256),
            ]);

        $response->assertSessionHasErrors('name');
    }

    // T023: test_user_is_switched_to_new_workspace_after_creation
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

    // T024: test_guest_cannot_create_workspace
    public function test_guest_cannot_create_workspace(): void
    {
        $response = $this->post(route('workspace.store'), [
            'name' => 'Workspace',
        ]);

        $response->assertRedirect('/auth/login');
    }

    // T025: test_user_can_switch_between_workspaces
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

    // T026: test_guest_cannot_access_workspace_creation_page
    public function test_guest_cannot_access_workspace_creation_page(): void
    {
        $response = $this->get(route('workspace.create'));

        $response->assertRedirect('/auth/login');
    }

    // T061: test_owner_can_update_workspace_name
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

    // T062: test_owner_can_update_workspace_description
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

    // T063: test_workspace_slug_validation_on_update
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

    // T064: test_member_cannot_update_workspace
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

    // T065: test_owner_can_delete_workspace
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

    // T066: test_member_cannot_delete_workspace
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

    // T067: test_workspace_deletion_removes_associated_data
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

    // T068: test_workspace_deletion_with_projects_succeeds
    public function test_workspace_deletion_with_projects_succeeds(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['user_id' => $user->id]);
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $project = Project::create([
            'name' => 'Projeto Teste',
            'description' => 'Descrição',
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('workspace.destroy', $workspace));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
        // Projetos são excluídos em cascata junto com o workspace (onDelete: cascade)
        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    // T087: test_owner_can_transfer_ownership
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

    // T088: test_ownership_transfer_demotes_old_owner
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

    // T089: test_ownership_transfer_promotes_new_owner
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

    // T090: test_cannot_transfer_to_non_member
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

        // Owner should still be the owner
        $this->assertDatabaseHas('members', [
            'user_id' => $owner->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Owner->value,
        ]);
    }

    // T091: test_admin_cannot_transfer_ownership
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

        $response->assertStatus(403);
    }

    // T092: test_member_cannot_transfer_ownership
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

        $response->assertStatus(403);
    }
}
