<?php

namespace Tests\Feature;

use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_user_with_arguments(): void
    {
        $this->artisan('create:user', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            '--role' => 'member',
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'member',
        ]);
    }

    public function test_can_create_admin_user_with_flag(): void
    {
        $this->artisan('create:user', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            '--admin' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_can_create_admin_user_with_role_option(): void
    {
        $this->artisan('create:user', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            '--role' => 'admin',
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_cannot_create_user_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->artisan('create:user', [
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            '--role' => 'member',
        ])->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_flag_takes_precedence_over_role_option(): void
    {
        $this->artisan('create:user', [
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'password123',
            '--admin' => true,
            '--role' => 'member',
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_outputs_user_info_on_success(): void
    {
        $this->artisan('create:user', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            '--role' => 'member',
        ])
            ->expectsOutputToContain('Usuário criado com sucesso')
            ->expectsOutputToContain('Jane Doe')
            ->expectsOutputToContain('jane@example.com')
            ->expectsOutputToContain('member')
            ->assertSuccessful();
    }

    public function test_outputs_error_on_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->artisan('create:user', [
            'name' => 'User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            '--role' => 'member',
        ])
            ->expectsOutputToContain('Já existe um usuário com o email')
            ->assertFailed();
    }

    public function test_password_is_hashed(): void
    {
        $this->artisan('create:user', [
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'plain-password',
            '--role' => 'member',
        ])->assertSuccessful();

        $user = User::where('email', 'user@example.com')->first();

        $this->assertNotEquals('plain-password', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('plain-password', $user->password));
    }
}
