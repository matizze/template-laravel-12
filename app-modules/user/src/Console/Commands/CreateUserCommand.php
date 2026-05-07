<?php

declare(strict_types=1);

namespace Modules\User\Console\Commands;

use App\Enums\RoleName;
use Illuminate\Console\Command;
use Modules\Permission\Services\RoleAssigner;
use Modules\User\Models\User;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateUserCommand extends Command
{
    protected $signature = 'create:user {name?} {email?} {password?} {--role=} {--admin}';

    protected $description = 'Create a new user';

    public function handle(RoleAssigner $roles): int
    {
        $name = $this->argument('name') ?: text(label: 'Nome do usuário', required: true);

        $email = $this->argument('email') ?: text(label: 'Email do usuário', required: true);

        if (User::query()->where('email', $email)->exists()) {
            $this->error("Já existe um usuário com o email: {$email}");

            return self::FAILURE;
        }

        $password = $this->argument('password') ?: password(label: 'Senha do usuário', required: true);

        $roleName = match (true) {
            (bool) $this->option('admin') => RoleName::Admin,
            (bool) $this->option('role') => RoleName::from((string) $this->option('role')),
            default => RoleName::from(select(
                label: 'Role do usuário',
                options: [RoleName::Member->value, RoleName::Admin->value],
                default: RoleName::Member->value,
            )),
        };

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $roles->ensure($roleName);
        $roles->assign($user, $roleName);

        $this->newLine();
        $this->line('==============================');
        $this->info('Usuário criado com sucesso');
        $this->line('==============================');
        $this->line("Name     : {$name}");
        $this->line("Email    : {$email}");
        $this->line("Role     : {$roleName->value}");
        $this->line('==============================');
        $this->newLine();

        return self::SUCCESS;
    }
}
