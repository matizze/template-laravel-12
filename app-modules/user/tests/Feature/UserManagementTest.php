<?php

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role_name' => 'member',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'New User',
                'email' => 'newuser@example.com',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);

        $newUser = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue($newUser->load('roles')->roles->contains('name', 'member'));
    }

    public function test_admin_can_create_admin_user(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'New Admin',
            'email' => 'admin2@example.com',
            'password' => 'password123',
            'role_name' => 'admin',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'admin2@example.com',
        ]);

        $newAdmin = User::where('email', 'admin2@example.com')->first();
        $this->assertTrue($newAdmin->load('roles')->roles->contains('name', 'admin'));
    }

    public function test_member_cannot_create_user(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role_name' => 'member',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('users', ['email' => 'newuser@example.com']);
    }

    public function test_guest_cannot_create_user(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role_name' => 'member',
        ]);

        $response->assertStatus(401);
    }

    public function test_create_user_requires_valid_data(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role_name']);
    }

    public function test_create_user_requires_unique_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'role_name' => 'member',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_create_user_requires_valid_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role_name' => 'superadmin',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('role_name');
    }

    public function test_admin_can_update_user_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $memberRole = Role::where('name', 'member')
            ->whereNull('tenant_id')
            ->firstOrFail();
        $memberRole->assign($user);

        $response = $this->actingAs($admin)->patchJson("/api/users/{$user->id}", [
            'role_name' => 'admin',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertTrue($user->roles->contains('name', 'admin'));
        $this->assertFalse($user->roles->contains('name', 'member'));
    }

    public function test_admin_cannot_update_own_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patchJson("/api/users/{$admin->id}", [
            'role_name' => 'member',
        ]);

        $response->assertStatus(403);

        $admin->refresh();
        $this->assertTrue($admin->roles->contains('name', 'admin'));
    }

    public function test_member_cannot_update_user_role(): void
    {
        $member = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($member)->patchJson("/api/users/{$otherUser->id}", [
            'role_name' => 'admin',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Usuário deletado com sucesso!']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/users/{$admin->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_member_cannot_delete_user(): void
    {
        $member = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($member)->deleteJson("/api/users/{$otherUser->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
    }
}
