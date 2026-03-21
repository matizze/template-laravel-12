<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWorkspace;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesWorkspace;
    use RefreshDatabase;

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Bem-vindo');
    }

    public function test_dashboard_shows_workspace_switcher(): void
    {
        [$user, $workspace] = $this->createOwnerWithWorkspace();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('workspace-switcher');
        $response->assertSee($workspace->name);
    }
}
