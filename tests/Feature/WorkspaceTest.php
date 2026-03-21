<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_workspace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/workspace', [
            'name' => 'My Workspace',
            'slug' => 'my-workspace',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('workspaces', ['slug' => 'my-workspace']);
    }

    public function test_workspace_slug_must_be_unique(): void
    {
        $user = User::factory()->create();
        Workspace::factory()->for($user, 'owner')->create(['slug' => 'dup']);

        $response = $this->actingAs($user)->post('/workspace', [
            'name' => 'Another',
            'slug' => 'dup',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_guest_cannot_create_workspace(): void
    {
        $response = $this->post('/workspace', ['name' => 'Test', 'slug' => 'test']);

        $response->assertRedirect('/auth/login');
    }
}
