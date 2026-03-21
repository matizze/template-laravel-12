<?php

namespace Tests\Feature;

use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_is_accessible(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_settings(): void
    {
        $response = $this->get(route('settings.index'));

        $response->assertRedirect('/auth/login');
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('settings.profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'profile']));

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
    }

    public function test_profile_update_requires_name_and_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('settings.profile.update'), []);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    public function test_profile_update_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('settings.profile.update'), [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_keep_same_email_on_profile_update(): void
    {
        $user = User::factory()->create(['email' => 'same@example.com']);

        $response = $this->actingAs($user)->patch(route('settings.profile.update'), [
            'name' => 'New Name',
            'email' => 'same@example.com',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'profile']));

    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password1',
        ]);

        $response = $this->actingAs($user)->patch(route('settings.password.update'), [
            'current_password' => 'old-password1',
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'password']));

    }

    public function test_password_update_requires_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password1',
        ]);

        $response = $this->actingAs($user)->patch(route('settings.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password1',
            'password_confirmation' => 'new-password1',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_password_update_requires_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password1',
        ]);

        $response = $this->actingAs($user)->patch(route('settings.password.update'), [
            'current_password' => 'old-password1',
            'password' => 'new-password1',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_user_can_delete_account(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $response = $this->actingAs($user)->delete(route('settings.account.destroy'), [
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertGuest();
    }

    public function test_account_deletion_requires_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $response = $this->actingAs($user)->delete(route('settings.account.destroy'), [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_users_tab_is_accessible_for_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('settings.index', ['tab' => 'users']));

        $response->assertStatus(200);
    }

    public function test_users_tab_is_forbidden_for_member(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->get(route('settings.index', ['tab' => 'users']));

        $response->assertStatus(403);
    }
}
