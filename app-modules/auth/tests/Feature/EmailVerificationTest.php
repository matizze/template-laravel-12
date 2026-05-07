<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Modules\User\Models\User;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function buildSignedUrl(User $user, ?string $hashOverride = null): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Date::now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => $hashOverride ?? sha1($user->getEmailForVerification()),
            ]
        );
    }

    public function test_register_dispatches_verified_email_notification(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Verifier',
            'email' => 'verifier@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'verifier@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verify_with_valid_signed_url_marks_email_as_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $url = $this->buildSignedUrl($user);

        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email verified.',
                'verified' => true,
            ]);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verify_is_idempotent_for_already_verified_user(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->hasVerifiedEmail());

        $url = $this->buildSignedUrl($user);

        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email already verified.',
                'verified' => true,
            ]);
    }

    public function test_verify_with_invalid_hash_returns_403(): void
    {
        $user = User::factory()->unverified()->create();

        $url = $this->buildSignedUrl($user, sha1('not-the-real-email@example.com'));

        $response = $this->getJson($url);

        $response->assertStatus(403);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verify_with_tampered_signature_returns_403(): void
    {
        $user = User::factory()->unverified()->create();

        $url = $this->buildSignedUrl($user);

        $tampered = preg_replace('/signature=[^&]+/', 'signature=deadbeef', $url);

        $response = $this->getJson($tampered);

        $response->assertStatus(403);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_sends_notification_for_unverified_user(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/email/verification-notification');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Verification email sent.']);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_is_noop_for_verified_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/email/verification-notification');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email already verified.',
                'verified' => true,
            ]);

        Notification::assertNothingSent();
    }

    public function test_resend_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/email/verification-notification');

        $response->assertStatus(401);
    }
}
