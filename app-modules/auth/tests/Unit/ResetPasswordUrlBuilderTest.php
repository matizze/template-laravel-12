<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Notifications\ResetPasswordUrlBuilder;
use Modules\User\Models\User;
use Tests\TestCase;

class ResetPasswordUrlBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_produces_url_with_token_and_email(): void
    {
        config(['app.frontend_url' => 'https://app.example.com']);

        $user = User::factory()->create(['email' => 'jane@example.com']);
        $builder = new ResetPasswordUrlBuilder;

        $url = $builder($user, 'plain-token');

        $this->assertStringStartsWith('https://app.example.com/reset-password?', $url);
        $this->assertStringContainsString('token=plain-token', $url);
        $this->assertStringContainsString('email=jane%40example.com', $url);
    }

    public function test_email_is_url_encoded(): void
    {
        config(['app.frontend_url' => 'https://app.example.com']);

        $user = User::factory()->create(['email' => 'a+b@example.com']);
        $builder = new ResetPasswordUrlBuilder;

        $url = $builder($user, 'tk');

        $this->assertStringContainsString('email=a%2Bb%40example.com', $url);
    }

    public function test_falls_back_to_app_url_when_frontend_url_is_not_set(): void
    {
        config(['app.frontend_url' => 'http://fallback.test']);

        $user = User::factory()->create(['email' => 'foo@example.com']);
        $builder = new ResetPasswordUrlBuilder;

        $url = $builder($user, 'tk');

        $this->assertStringStartsWith('http://fallback.test/reset-password?', $url);
    }

    public function test_trailing_slash_in_frontend_url_is_trimmed(): void
    {
        config(['app.frontend_url' => 'https://app.example.com/']);

        $user = User::factory()->create(['email' => 'foo@example.com']);
        $builder = new ResetPasswordUrlBuilder;

        $url = $builder($user, 'tk');

        $this->assertStringStartsWith('https://app.example.com/reset-password?', $url);
        $this->assertStringNotContainsString('.com//reset-password', $url);
    }

    public function test_tokens_with_special_chars_are_url_encoded(): void
    {
        config(['app.frontend_url' => 'https://app.example.com']);

        $user = User::factory()->create(['email' => 'foo@example.com']);
        $builder = new ResetPasswordUrlBuilder;

        $token = 'abc/def+ghi=jkl&mno';
        $url = $builder($user, $token);

        $parts = parse_url($url);
        $this->assertIsArray($parts);
        $this->assertArrayHasKey('query', $parts);

        parse_str($parts['query'], $query);

        $this->assertSame($token, $query['token']);
        $this->assertSame('foo@example.com', $query['email']);
    }
}
