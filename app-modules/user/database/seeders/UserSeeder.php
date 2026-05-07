<?php

declare(strict_types=1);

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory(10)->create();

        User::factory()
            ->superadmin()
            ->create([
                'name' => 'Super Admin',
                'email' => 'user@example.com',
            ]);

        User::factory()
            ->admin()
            ->create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
            ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        foreach (['alice', 'bob', 'charlie', 'diana', 'eve'] as $name) {
            User::factory()->create([
                'name' => ucfirst($name),
                'email' => "{$name}@example.com",
            ]);
        }
    }
}
