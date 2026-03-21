<?php

namespace Modules\User\Console\Commands;

use Modules\User\Models\User;
use Illuminate\Console\Command;

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
            $role = 'admin';
        } else {
            $role = $this->option('role') ?: select(
                label: 'Role do usuário',
                options: ['member', 'admin'],
                default: 'member'
            );
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ]);

        $this->newLine();
        $this->line('==============================');
        $this->info('Usuário criado com sucesso');
        $this->line('==============================');
        $this->line("Name     : {$name}");
        $this->line("Email    : {$email}");
        $this->line("Role     : {$role}");
        $this->line('==============================');
        $this->newLine();

        return self::SUCCESS;
    }
}
