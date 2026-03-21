<?php

namespace Tests\Feature;

use Modules\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_is_accessible(): void
    {
        $response = $this->get('/auth/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/auth/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_link_requires_email(): void
    {
        $response = $this->post('/auth/forgot-password', []);

        $response->assertSessionHasErrors('email');
    }

    public function test_reset_password_link_requires_valid_email(): void
    {
        $response = $this->post('/auth/forgot-password', ['email' => 'not-an-email']);

        $response->assertSessionHasErrors('email');
    }

    public function test_reset_password_link_with_nonexistent_email_does_not_fail(): void
    {
        Notification::fake();

        $response = $this->post('/auth/forgot-password', ['email' => 'nobody@example.com']);

        $response->assertRedirect();
        Notification::assertNothingSent();
    }

    public function test_reset_password_page_is_accessible(): void
    {
        $response = $this->get('/auth/reset-password/fake-token?email=test@example.com');

        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/auth/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $response = $this->post('/auth/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ]);

            $response->assertRedirect(route('login'));

            $user->refresh();

            $this->assertTrue(Hash::check('new-password123', $user->password));

            return true;
        });
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect();

        $user->refresh();

        $this->assertFalse(Hash::check('new-password123', $user->password));
    }

    public function test_reset_password_requires_password_confirmation(): void
    {
        $response = $this->post('/auth/reset-password', [
            'token' => 'some-token',
            'email' => 'test@example.com',
            'password' => 'new-password123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_reset_password_requires_all_fields(): void
    {
        $response = $this->post('/auth/reset-password', []);

        $response->assertSessionHasErrors(['token', 'email', 'password']);
    }

    public function test_authenticated_user_cannot_access_forgot_password_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/auth/forgot-password');

        $response->assertRedirect('/dashboard');
    }

    public function test_user_can_login_with_remember_me(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $response = $this->post('/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
            'remember' => '1',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $user->refresh();

        $this->assertNotNull($user->remember_token);
    }

    public function test_login_without_remember_me(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $response = $this->post('/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        Notification::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/auth/forgot-password', [
                'email' => "user{$i}@example.com",
            ]);
        }

        $response = $this->post('/auth/forgot-password', [
            'email' => 'another@example.com',
        ]);

        $response->assertStatus(429);
    }
}
