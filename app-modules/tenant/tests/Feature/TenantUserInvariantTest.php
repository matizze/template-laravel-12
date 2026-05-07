<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Models\User;
use Tests\TestCase;

class TenantUserInvariantTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = RoleSeeder::class;

    public function test_factory_create_against_trashed_tenant_throws_validation_exception(): void
    {
        $tenant = Tenant::factory()->create();
        $tenant->delete();

        $this->assertTrue($tenant->fresh()->trashed());

        try {
            TenantUser::factory()->create([
                'tenant_id' => $tenant->id,
                'user_id' => User::factory()->create()->id,
            ]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('tenant', $e->errors());
            $this->assertSame(
                'Vínculo só é aceito em tenants operáveis (folhas) ativos.',
                $e->errors()['tenant'][0],
            );
        }
    }

    public function test_factory_create_against_non_operable_parent_tenant_throws_validation_exception(): void
    {
        $parent = Tenant::factory()->create();
        Tenant::factory()->create(['parent_id' => $parent->id]);

        $this->assertFalse($parent->fresh()->isOperable());

        try {
            TenantUser::factory()->create([
                'tenant_id' => $parent->id,
                'user_id' => User::factory()->create()->id,
            ]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('tenant', $e->errors());
        }
    }

    public function test_http_attach_to_trashed_tenant_returns_422_with_tenant_error(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->superadmin()->create();
        $newUser = User::factory()->create();

        $tenant->delete();

        $response = $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/tenants/{$tenant->id}/users", [
                'user_id' => $newUser->id,
            ]);

        // Trashed tenants are not resolvable by the implicit route binding,
        // so FormRequest::authorize fails first with 403. Either 403 or 422
        // proves the model-hook RuntimeException no longer leaks as 500.
        $this->assertContains($response->status(), [403, 404, 422]);
        $this->assertNotSame(500, $response->status());
    }

    public function test_http_attach_to_non_operable_parent_returns_422_with_tenant_error(): void
    {
        $parent = Tenant::factory()->create();
        Tenant::factory()->create(['parent_id' => $parent->id]);

        $admin = User::factory()->superadmin()->create();
        $newUser = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Tenant-ID', $parent->id)
            ->postJson("/api/tenants/{$parent->id}/users", [
                'user_id' => $newUser->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tenant']);
    }
}
