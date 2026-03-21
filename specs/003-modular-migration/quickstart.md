# Quickstart: Modular Monolith Migration

**Feature**: 003-modular-migration
**Date**: 2026-03-21

## Pré-requisitos

- Laravel 13 com estrutura simplificada
- PHP 8.4+
- Composer instalado

## Setup Rápido

### 1. Criar estrutura dos módulos

```bash
mkdir -p app-modules/{core,user,auth,workspace}/{src,routes,resources,tests,database}
```

### 2. Criar composer.json de cada módulo

Cada módulo precisa do seu `composer.json` com PSR-4 e Laravel provider discovery.

### 3. Registar path repositories no root composer.json

```bash
# Adicionar repositories e requires ao composer.json raiz
composer config repositories.core path app-modules/core
composer config repositories.user path app-modules/user
composer config repositories.auth path app-modules/auth
composer config repositories.workspace path app-modules/workspace

composer require modules/core:*@dev modules/user:*@dev modules/auth:*@dev modules/workspace:*@dev
```

### 4. Verificar instalação

```bash
composer dump-autoload
php artisan test --compact
```

## Ordem de migração

1. **Core** — mover AppServiceProvider, componentes Blade, layouts, dashboard
2. **User** — mover User model, SettingsController, UserController, CreateUserCommand
3. **Auth** — mover controllers/requests/views de autenticação, routes/auth.php
4. **Workspace** — mover models, controllers, middleware, views, criar HasWorkspaces trait

## Comandos úteis durante a migração

```bash
# Executar todos os testes
php artisan test --compact

# Executar testes de um módulo específico
php artisan test app-modules/user/tests/
php artisan test app-modules/auth/tests/

# Regenerar autoload após mover ficheiros
composer dump-autoload

# PHPStan
./vendor/bin/phpstan analyse

# Pint
./vendor/bin/pint --dirty --format agent
```

## Verificação final

```bash
# Todos os testes passam
php artisan test --compact

# PHPStan sem erros
./vendor/bin/phpstan analyse

# Pint formatação
./vendor/bin/pint --dirty --format agent

# Verificar que Workspace é removível
# (remover directório + trait + composer entry, testes de Core/User/Auth devem passar)
```
