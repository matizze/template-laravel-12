<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Models\Tenant;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_depth_returns_one_for_root(): void
    {
        $root = Tenant::factory()->root()->create();

        $this->assertSame(1, $root->depth());
    }

    public function test_depth_returns_two_for_level_two(): void
    {
        $root = Tenant::factory()->root()->create();
        $child = Tenant::factory()->withParent($root)->create();

        $this->assertSame(2, $child->depth());
    }

    public function test_depth_returns_three_for_level_three(): void
    {
        $root = Tenant::factory()->root()->create();
        $child = Tenant::factory()->withParent($root)->create();
        $grandchild = Tenant::factory()->withParent($child)->create();

        $this->assertSame(3, $grandchild->depth());
    }

    public function test_descendants_returns_full_subtree(): void
    {
        $root = Tenant::factory()->root()->create();
        $a = Tenant::factory()->withParent($root)->create();
        $b = Tenant::factory()->withParent($root)->create();
        $a1 = Tenant::factory()->withParent($a)->create();

        $descendants = $root->descendants();
        $ids = $descendants->pluck('id')->all();

        $this->assertCount(3, $descendants);
        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
        $this->assertContains($a1->id, $ids);
        $this->assertNotContains($root->id, $ids);
    }

    public function test_ancestors_returns_root_to_immediate_parent_in_order(): void
    {
        $root = Tenant::factory()->root()->create(['name' => 'Root']);
        $child = Tenant::factory()->withParent($root)->create(['name' => 'Child']);
        $grandchild = Tenant::factory()->withParent($child)->create(['name' => 'Grand']);

        $ancestors = $grandchild->ancestors();
        $names = $ancestors->pluck('name')->all();

        $this->assertSame(['Root', 'Child'], $names);
    }

    public function test_path_joins_names_with_chevron(): void
    {
        $root = Tenant::factory()->root()->create(['name' => 'Root']);
        $child = Tenant::factory()->withParent($root)->create(['name' => 'Child']);
        $grandchild = Tenant::factory()->withParent($child)->create(['name' => 'Grand']);

        $this->assertSame('Root › Child › Grand', $grandchild->path());
    }

    public function test_scope_operable_returns_only_leaves(): void
    {
        $root = Tenant::factory()->root()->create();
        $leafSibling = Tenant::factory()->root()->create();
        $child = Tenant::factory()->withParent($root)->create();

        $operable = Tenant::query()->operable()->pluck('id')->all();

        $this->assertContains($leafSibling->id, $operable);
        $this->assertContains($child->id, $operable);
        $this->assertNotContains($root->id, $operable);
    }

    public function test_parent_id_is_in_fillable_regression(): void
    {
        $tenant = new Tenant;

        $this->assertContains('parent_id', $tenant->getFillable());
    }

    public function test_soft_deletes_keeps_row_in_only_trashed(): void
    {
        $tenant = Tenant::factory()->create();
        $id = $tenant->id;

        $tenant->delete();

        $this->assertNull(Tenant::query()->find($id));
        $this->assertNotNull(Tenant::onlyTrashed()->find($id));
    }
}
