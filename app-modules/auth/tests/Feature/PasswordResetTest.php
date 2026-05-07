<?php

namespace Tests\Feature;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\User\Models\User;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'If the email is registered, you will receive a password reset link.']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_link_requires_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_reset_password_link_requires_valid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'not-an-email']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_reset_password_link_with_nonexistent_email_returns_success(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com']);

        $response->assertStatus(200)
            ->assertJson(['message' => 'If the email is registered, you will receive a password reset link.']);

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $response = $this->postJson('/api/v1/auth/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password123',
            ]);

            $response->assertStatus(200)
                ->assertJson(['message' => 'Password has been reset successfully.']);

            $user->refresh();

            $this->assertTrue(Hash::check('new-password123', $user->password));

            return true;
        });
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password123',
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Unable to reset password. Please request a new link.']);

        $user->refresh();

        $this->assertFalse(Hash::check('new-password123', $user->password));
    }

    public function test_reset_password_requires_all_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'email', 'password']);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        Notification::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', [
                'email' => "user{$i}@example.com",
            ]);
        }

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'another@example.com',
        ]);

        $response->assertStatus(429);
    }
}
