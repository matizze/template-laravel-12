<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Tests\TestCase;

class AccountDeletionAtomicityTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_deletion_removes_user_tokens_and_tenant_pivots_atomically(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $user->tenants()->attach([$tenantA->id, $tenantB->id]);
        $user->createToken('auth-token');
        $user->createToken('mobile-token');

        $this->assertDatabaseHas('tenant_user', ['user_id' => $user->id, 'tenant_id' => $tenantA->id]);
        $this->assertDatabaseHas('tenant_user', ['user_id' => $user->id, 'tenant_id' => $tenantB->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson('/api/user/account', [
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Conta excluída com sucesso!']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('tenant_user', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }
}
