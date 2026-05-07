<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Rules\ParentHasNoActiveLinks;
use Modules\User\Models\User;
use Tests\TestCase;

class ParentHasNoActiveLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_passes_when_parent_has_no_users(): void
    {
        $parent = Tenant::factory()->create();
        $rule = new ParentHasNoActiveLinks;

        $failed = false;
        $rule->validate('parent_id', $parent->id, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_rule_fails_when_parent_has_active_links(): void
    {
        $parent = Tenant::factory()->create();
        $user = User::factory()->create();
        $parent->users()->attach($user);

        $rule = new ParentHasNoActiveLinks;

        $message = null;
        $rule->validate('parent_id', $parent->id, function (string $msg) use (&$message): void {
            $message = $msg;
        });

        $this->assertNotNull($message);
        $this->assertStringContainsString('vínculos', $message);
    }

    public function test_rule_passes_when_value_is_null(): void
    {
        $rule = new ParentHasNoActiveLinks;

        $failed = false;
        $rule->validate('parent_id', null, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_rule_passes_when_parent_does_not_exist(): void
    {
        $rule = new ParentHasNoActiveLinks;

        $failed = false;
        $rule->validate('parent_id', 999999, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed);
    }
}
