<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Models\Tenant;
use Tests\TestCase;

class TenantHierarchyPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Performance gate per SC-005: descendants() must complete within 200ms
     * for a moderately deep tree.
     *
     * Tree shape: 4 levels × 5 fanout = 5 + 25 + 125 + 625 = 780 descendants.
     */
    public function test_descendants_within_budget(): void
    {
        $root = Tenant::factory()->root()->create();
        $this->buildTree($root, depth: 4, fanout: 5);

        $start = hrtime(true);
        $descendants = $root->descendants();
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        $expected = 5 + 25 + 125 + 625;
        $this->assertCount($expected, $descendants);
        $this->assertLessThan(
            200,
            $elapsedMs,
            "descendants() exceeded 200ms (got {$elapsedMs}ms)"
        );
    }

    private function buildTree(Tenant $parent, int $depth, int $fanout): void
    {
        if ($depth === 0) {
            return;
        }

        $children = Tenant::factory()->count($fanout)->create(['parent_id' => $parent->id]);

        foreach ($children as $child) {
            $this->buildTree($child, $depth - 1, $fanout);
        }
    }
}
