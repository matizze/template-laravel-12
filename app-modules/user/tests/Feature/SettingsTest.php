<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\User\Models\User;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/v1/user/profile');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/v1/user/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $user->refresh();

        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
    }

    public function test_profile_update_requires_name_and_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/v1/user/profile', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_profile_update_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/v1/user/profile', [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_user_can_keep_same_email_on_profile_update(): void
    {
        $user = User::factory()->create(['email' => 'same@example.com']);

        $response = $this->actingAs($user)->patchJson('/api/v1/user/profile', [
            'name' => 'New Name',
            'email' => 'same@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'New Name',
                'email' => 'same@example.com',
            ]);
    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password1',
        ]);

        $response = $this->actingAs($user)->patchJson('/api/v1/user/password', [
            'current_password' => 'old-password1',
            'password' => 'new-password1',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Senha atualizada com sucesso!']);

        $user->refresh();

        $this->assertTrue(Hash::check('new-password1', $user->password));
    }

    public function test_password_update_requires_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password1',
        ]);

        $response = $this->actingAs($user)->patchJson('/api/v1/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_user_can_delete_account(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->deleteJson('/api/v1/user/account', [
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Conta excluída com sucesso!']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_account_deletion_requires_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->deleteJson('/api/v1/user/account', [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
