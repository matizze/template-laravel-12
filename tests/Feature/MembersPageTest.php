<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWorkspace;
use Tests\TestCase;

class MembersPageTest extends TestCase
{
    use CreatesWorkspace;
    use RefreshDatabase;

    public function test_owner_can_access_members_page(): void
    {
        [$user, $workspace] = $this->createOwnerWithWorkspace();

        $response = $this->actingAs($user)
            ->get(route('members.index', $workspace));

        $response->assertStatus(200);
        $response->assertSee('Membros do workspace');
    }

    public function test_invite_modal_has_listen_event(): void
    {
        [$user, $workspace] = $this->createOwnerWithWorkspace();

        $response = $this->actingAs($user)
            ->get(route('members.index', $workspace));

        $html = $response->getContent();

        $this->assertStringContainsString('open-invite-modal', $html);
        $this->assertStringContainsString('dusk="invite-modal"', $html);
        $this->assertStringContainsString('x-on:open-invite-modal.window', $html);
    }
}
