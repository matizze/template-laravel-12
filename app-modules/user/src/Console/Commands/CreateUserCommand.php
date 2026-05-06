<?php

namespace Modules\User\Console\Commands;

use Illuminate\Console\Command;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateUserCommand extends Command
{
    protected $signature = 'create:user {name?} {email?} {password?} {--role=} {--admin}';

    protected $description = 'Create a new user';

    public function handle(): int
    {
        $name = $this->argument('name') ?: text(label: 'Nome do usuário', required: true);

        $email = $this->argument('email') ?: text(label: 'Email do usuário', required: true);

        if (User::query()->where('email', $email)->exists()) {
            $this->error("Já existe um usuário com o email: {$email}");

            return self::FAILURE;
        }

        $password = $this->argument('password') ?: password(label: 'Senha do usuário', required: true);

        if ($this->option('admin')) {
            $roleName = 'admin';
        } else {
            $roleName = $this->option('role') ?: select(
                label: 'Role do usuário',
                options: ['member', 'admin'],
                default: 'member'
            );
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $role = Role::firstOrCreate(
            ['name' => $roleName, 'tenant_id' => null],
            ['permissions' => $roleName === 'admin'
                ? ['users' => ['create', 'update', 'delete']]
                : []]
        );
        $role->assign($user);

        $this->newLine();
        $this->line('==============================');
        $this->info('Usuário criado com sucesso');
        $this->line('==============================');
        $this->line("Name     : {$name}");
        $this->line("Email    : {$email}");
        $this->line("Role     : {$roleName}");
        $this->line('==============================');
        $this->newLine();

        return self::SUCCESS;
    }
}
