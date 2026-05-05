<?php

namespace Modules\Tenant\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\CurrentTenantManager;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentTenantManager::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'tenant');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Blade::anonymousComponentPath(__DIR__.'/../../resources/components', 'tenant');

        View::composer('*', function ($view): void {
            if (! str_contains($view->name(), 'layout.dashboard')) {
                return;
            }

            $user = Auth::user();

            if (! $user) {
                $view->with([
                    'tenants' => collect(),
                    'currentTenant' => null,
                    'needsPath' => false,
                ]);

                return;
            }

            $tenants = $user->tenants()
                ->whereDoesntHave('children')
                ->whereNull('tenants.deleted_at')
                ->orderBy('tenants.name')
                ->get();

            $needsPath = $tenants->groupBy('name')->contains(fn ($group) => $group->count() > 1);

            $view->with([
                'tenants' => $tenants,
                'currentTenant' => Tenant::current(),
                'needsPath' => $needsPath,
            ]);
        });
    }
}
