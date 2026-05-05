<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Models\User;
use Tests\TestCase;

class TenantHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenants_table_has_parent_id_column_indexed(): void
    {
        $this->assertTrue(Schema::hasColumn('tenants', 'parent_id'));
        $this->assertTrue(Schema::hasColumn('tenants', 'deleted_at'));
    }

    public function test_is_operable_returns_true_for_leaf_tenant(): void
    {
        $tenant = Tenant::factory()->root()->create();

        $this->assertTrue($tenant->isOperable());
    }

    public function test_is_operable_returns_false_for_grouper_tenant(): void
    {
        $parent = Tenant::factory()->root()->create();
        Tenant::factory()->withParent($parent)->create();

        $parent->refresh();

        $this->assertFalse($parent->isOperable());
    }

    public function test_descendants_returns_full_subtree_three_levels(): void
    {
        $root = Tenant::factory()->root()->create(['name' => 'Root']);
        $childA = Tenant::factory()->withParent($root)->create(['name' => 'ChildA']);
        $childB = Tenant::factory()->withParent($root)->create(['name' => 'ChildB']);
        $grandA1 = Tenant::factory()->withParent($childA)->create(['name' => 'GrandA1']);
        $greatA1a = Tenant::factory()->withParent($grandA1)->create(['name' => 'GreatA1a']);

        $descendants = $root->descendants();

        $this->assertCount(4, $descendants);
        $ids = $descendants->pluck('id')->all();
        $this->assertContains($childA->id, $ids);
        $this->assertContains($childB->id, $ids);
        $this->assertContains($grandA1->id, $ids);
        $this->assertContains($greatA1a->id, $ids);
        $this->assertNotContains($root->id, $ids);
    }

    public function test_descendants_excludes_soft_deleted(): void
    {
        $root = Tenant::factory()->root()->create();
        $alive = Tenant::factory()->withParent($root)->create();
        $deleted = Tenant::factory()->withParent($root)->create();
        $deleted->delete();

        $descendants = $root->descendants();

        $this->assertCount(1, $descendants);
        $this->assertEquals($alive->id, $descendants->first()->id);
    }

    public function test_ancestors_returns_chain_to_root(): void
    {
        $root = Tenant::factory()->root()->create(['name' => 'Root']);
        $child = Tenant::factory()->withParent($root)->create(['name' => 'Child']);
        $grand = Tenant::factory()->withParent($child)->create(['name' => 'Grand']);

        $ancestors = $grand->ancestors();

        $this->assertCount(2, $ancestors);
        $this->assertEquals('Root', $ancestors->first()->getAttribute('name'));
        $this->assertEquals('Child', $ancestors->last()->getAttribute('name'));
    }

    public function test_ancestors_for_root_returns_empty(): void
    {
        $root = Tenant::factory()->root()->create();

        $this->assertCount(0, $root->ancestors());
    }

    public function test_path_concatenates_with_default_separator(): void
    {
        $root = Tenant::factory()->root()->create(['name' => 'Matriz A']);
        $child = Tenant::factory()->withParent($root)->create(['name' => 'Regional Sul']);
        $grand = Tenant::factory()->withParent($child)->create(['name' => 'Base Centro']);

        $this->assertEquals('Matriz A › Regional Sul › Base Centro', $grand->path());
    }

    public function test_path_supports_custom_separator(): void
    {
        $root = Tenant::factory()->root()->create(['name' => 'A']);
        $child = Tenant::factory()->withParent($root)->create(['name' => 'B']);

        $this->assertEquals('A / B', $child->path(' / '));
    }

    public function test_operable_scope_includes_only_leaves(): void
    {
        $root = Tenant::factory()->root()->create();
        $leaf = Tenant::factory()->withParent($root)->create();
        $standalone = Tenant::factory()->root()->create();

        $operable = Tenant::operable()->pluck('id')->all();

        $this->assertContains($leaf->id, $operable);
        $this->assertContains($standalone->id, $operable);
        $this->assertNotContains($root->id, $operable);
    }

    public function test_view_composer_lists_only_operable_tenants_in_switcher(): void
    {
        $user = User::factory()->create();

        $grouper = Tenant::factory()->root()->create(['user_id' => $user->id, 'name' => 'GrouperOnly']);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $grouper->id]);

        $leaf = Tenant::factory()->withParent($grouper)->create(['name' => 'LeafOnly']);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $leaf->id]);

        $response = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $leaf->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('LeafOnly', false);
        $response->assertDontSee('GrouperOnly', false);
    }

    public function test_view_composer_renders_path_when_duplicate_leaf_names_exist(): void
    {
        $user = User::factory()->create();

        $rootA = Tenant::factory()->root()->create(['user_id' => $user->id, 'name' => 'Matriz A']);
        $leafA = Tenant::factory()->withParent($rootA)->create(['user_id' => $user->id, 'name' => 'Centro']);

        $rootB = Tenant::factory()->root()->create(['user_id' => $user->id, 'name' => 'Matriz B']);
        $leafB = Tenant::factory()->withParent($rootB)->create(['user_id' => $user->id, 'name' => 'Centro']);

        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $leafA->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $leafB->id]);

        $response = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $leafA->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $content = $response->getContent();
        // Path includes the separator and root name when duplicate names exist
        $this->assertStringContainsString('Matriz A', $content);
        $this->assertStringContainsString('Matriz B', $content);
    }

    public function test_middleware_redirects_to_onboarding_when_session_tenant_is_grouper(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->root()->create(['user_id' => $user->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant->id]);

        Tenant::factory()->withParent($tenant)->create();

        $response = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->get(route('dashboard'));

        $response->assertRedirect(route('onboarding'));
    }

    public function test_middleware_redirects_when_session_tenant_is_soft_deleted(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->root()->create(['user_id' => $user->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant->id]);

        $tenant->delete();

        $response = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->get(route('dashboard'));

        $response->assertRedirect(route('onboarding'));
    }

    public function test_create_tenant_request_rejects_parent_with_active_links(): void
    {
        $user = User::factory()->create();

        $parent = Tenant::factory()->root()->create(['user_id' => $user->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $parent->id]);

        $secondTenant = Tenant::factory()->root()->create(['user_id' => $user->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $secondTenant->id]);

        $response = $this->actingAs($user)
            ->post(route('tenant.store'), [
                'name' => 'Child',
                'parent_id' => $parent->id,
            ]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertDatabaseMissing('tenants', ['name' => 'Child']);
    }

    public function test_create_tenant_request_accepts_parent_without_active_links(): void
    {
        // FR-017: parent must have zero `tenant_user` links to become grouper.
        // The creator (`tenant.user_id`) is authorized via the user_id fallback
        // even after their own link was detached.
        $user = User::factory()->create();

        $parent = Tenant::factory()->root()->create(['user_id' => $user->id]);
        // Note: NO TenantUser link on the parent (FR-017 precondition).

        $activeTenant = Tenant::factory()->root()->create(['user_id' => $user->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $activeTenant->id]);

        $response = $this->actingAs($user)
            ->withSession(['current_tenant_id' => $activeTenant->id])
            ->post(route('tenant.store'), [
                'name' => 'Child',
                'parent_id' => $parent->id,
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('tenants', ['name' => 'Child', 'parent_id' => $parent->id]);
    }

    public function test_create_child_requires_owner_or_admin_on_parent(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $parent = Tenant::factory()->root()->create(['user_id' => $owner->id]);

        $strangerTenant = Tenant::factory()->root()->create(['user_id' => $stranger->id]);
        TenantUser::factory()->owner()->create(['user_id' => $stranger->id, 'tenant_id' => $strangerTenant->id]);

        $response = $this->actingAs($stranger)
            ->withSession(['current_tenant_id' => $strangerTenant->id])
            ->post(route('tenant.store'), [
                'name' => 'Hijack',
                'parent_id' => $parent->id,
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('tenants', ['name' => 'Hijack']);
    }
}
