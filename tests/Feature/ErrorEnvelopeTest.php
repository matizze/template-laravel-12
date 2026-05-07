<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Modules\Tenant\Models\Tenant;
use RuntimeException;
use Tests\TestCase;

class ErrorEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__test/model-not-found', function (): void {
            throw (new ModelNotFoundException)->setModel(Tenant::class, [9999]);
        });

        Route::post('/__test/validation', function (): void {
            throw ValidationException::withMessages([
                'name' => ['O nome é obrigatório.'],
            ]);
        });

        Route::get('/__test/boom', function (): void {
            throw new RuntimeException('Internal database meltdown at /var/secret/path:42');
        });
    }

    public function test_model_not_found_returns_404_with_message_envelope(): void
    {
        $response = $this->getJson('/__test/model-not-found');

        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
        $this->assertIsString($response->json('message'));
    }

    public function test_validation_exception_keeps_rich_errors_shape(): void
    {
        $response = $this->postJson('/__test/validation');

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => ['name'],
        ]);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_generic_exception_in_production_returns_internal_envelope_without_leak(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production', 'app.debug' => false]);

        $response = $this->getJson('/__test/boom');

        $response->assertStatus(500);
        $response->assertExactJson([
            'message' => 'Server error.',
            'code' => 'INTERNAL',
        ]);

        $body = $response->getContent();
        $this->assertIsString($body);
        $this->assertStringNotContainsString('RuntimeException', $body);
        $this->assertStringNotContainsString('/var/secret/path', $body);
        $this->assertStringNotContainsString('Internal database meltdown', $body);
    }
}
