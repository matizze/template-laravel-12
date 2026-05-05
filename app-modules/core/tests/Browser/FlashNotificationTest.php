<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Models\User;
use Tests\DuskTestCase;

class FlashNotificationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_flash_notification_appears_after_failed_login(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/login')
                ->type('#email', 'test@example.com')
                ->type('#password', 'wrong-password')
                ->press('Entrar')
                ->waitForText('E-mail ou senha incorretos')
                ->assertSee('E-mail ou senha incorretos');
        });
    }

    public function test_flash_notification_appears_after_profile_update(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->for($user, 'owner')->create();
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings?tab=profile')
                ->waitForText('Informações do perfil')
                ->clear('#name')
                ->type('#name', 'Updated Name')
                ->press('Salvar')
                ->waitForText('Perfil atualizado com sucesso')
                ->assertSee('Perfil atualizado com sucesso');
        });
    }

    public function test_flash_notification_appears_after_password_update(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);
        $tenant = Tenant::factory()->for($user, 'owner')->create();
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings?tab=password')
                ->waitForText('Atualizar senha')
                ->type('#current_password', 'password123')
                ->type('#password', 'new-password456')
                ->type('#password_confirmation', 'new-password456')
                ->press('Salvar senha')
                ->waitForText('Senha atualizada com sucesso')
                ->assertSee('Senha atualizada com sucesso');
        });
    }
}
