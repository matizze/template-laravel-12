<?php

namespace Database\Seeders;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory(10)->create()->each(function (User $user) {
            $this->createWorkspaceForUser($user, "{$user->name}'s Workspace", Str::slug($user->name).'-'.uniqid());
        });

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
        $this->createWorkspaceForUser($admin, "Admin's Workspace", 'admin-workspace');

        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $this->createWorkspaceForUser($testUser, "Test User's Workspace", 'test-user-workspace');
    }

    private function createWorkspaceForUser(User $user, string $name, string $slug): Workspace
    {
        $workspace = Workspace::create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => $slug,
        ]);

        $user->members()->create([
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Owner,
        ]);

        return $workspace;
    }
}
