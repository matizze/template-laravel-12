<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Tenant\Listeners\DetachUserFromTenants;
use Modules\Tenant\Models\Tenant;
use Modules\User\Events\UserDeleting;
use Modules\User\Models\User;
use Tests\TestCase;

class DetachUserFromTenantsListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_deletion_dispatches_user_deleting_event(): void
    {
        Event::fake([UserDeleting::class]);

        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/user/account', ['password' => 'password'])
            ->assertStatus(200);

        Event::assertDispatched(
            UserDeleting::class,
            fn (UserDeleting $event) => $event->user->is($user),
        );
    }

    public function test_listener_detaches_user_from_every_tenant(): void
    {
        $user = User::factory()->create();
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $user->tenants()->attach([$tenantA->id, $tenantB->id]);

        $this->assertDatabaseHas('tenant_user', ['user_id' => $user->id, 'tenant_id' => $tenantA->id]);
        $this->assertDatabaseHas('tenant_user', ['user_id' => $user->id, 'tenant_id' => $tenantB->id]);

        (new DetachUserFromTenants)->handle(new UserDeleting($user));

        $this->assertDatabaseMissing('tenant_user', ['user_id' => $user->id]);
    }
}
