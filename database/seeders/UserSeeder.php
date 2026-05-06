<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory(10)->create();

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
    }
}
