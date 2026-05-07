<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Laravel 12 Starter Template

Laravel 12 **API-only** starter template. Sanctum bearer-token authentication, modular monolith (InterNACHI/modular), RBAC permissions with JSON-tree role definitions, multi-tenant hierarchy, and production-ready deployment (Docker + Octane). No Blade views, no Vite, no Dusk.

## Installation

```bash
laravel new my-app --using=matizze/template-laravel-12 --phpunit --boost
```

> **Important:** This template uses **PHPUnit** and already includes **Laravel Boost**. Always pass `--phpunit --boost` (or use `--no-interaction`) to avoid prompts that could conflict with the template setup.
>
> Choosing **Pest** during installation may conflict with the existing PHPUnit test suite.

Alternatively, to skip all prompts:

```bash
laravel new my-app --using=matizze/template-laravel-12 --no-interaction
```

After installation:

```bash
cd my-app
composer setup
```

## Features

- Sanctum bearer-token authentication (login, register, logout, password reset)
- RBAC permissions with JSON-tree role definitions and `Gate::before` short-circuit
- Multi-tenant hierarchy (parent → children) with soft delete and membership pivot
- User management endpoints gated by named permissions (`users.create/update/delete`)
- OpenAPI docs auto-generated via Dedoc Scramble at `/docs/api`
- PHPUnit test suite (feature + unit; no Dusk)
- Docker + Octane (Swoole) deployment ready

## Common Commands

```bash
composer dev          # Start dev environment (server + queue + scheduler + logs)
composer test         # Run tests
composer setup        # Full project setup

./vendor/bin/phpstan analyse           # Static analysis
./vendor/bin/pint --dirty --format agent   # Code formatting

php artisan migrate:fresh --seed       # Reset database with seeders
php artisan create:user --admin        # Create admin user via CLI
```

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
