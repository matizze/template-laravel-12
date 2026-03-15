<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Laravel 12 Starter Template

Laravel 12 starter template with role-based access (admin/member), settings management, user CRUD, and production-ready deployment (Docker + Octane).

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

- Role-based access control (admin/member) with Gates
- User management CRUD (admin only)
- Settings management
- Password reset flow
- Flash notifications (PHPFlasher + Noty)
- Blade components (layouts, forms, modals, avatar, pagination)
- Tailwind CSS v4 + Alpine.js
- PHPUnit test suite (Feature + Browser/Dusk)
- Docker + Octane deployment ready

## Common Commands

```bash
composer dev          # Start dev environment (server + queue + scheduler + logs + Vite)
composer test         # Run tests
composer setup        # Full project setup

./vendor/bin/phpstan analyse   # Static analysis
./vendor/bin/pint              # Code formatting

php artisan migrate:fresh --seed   # Reset database with seeders
php artisan create:user --admin    # Create admin user via CLI
```

## License

Open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
