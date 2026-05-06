<?php

namespace Modules\Tenant\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Http\Middleware\SetCurrentTenant;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Policies\TenantPolicy;
use Modules\Tenant\Services\CurrentTenantManager;
use Modules\User\Models\User;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentTenantManager::class);
    }

    public function boot(Router $router): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $router->aliasMiddleware('tenant', SetCurrentTenant::class);

        Gate::policy(Tenant::class, TenantPolicy::class);

        Gate::define('tenants.create', fn (User $user): bool => true);

        Gate::define('tenants.users.view', fn (User $user, Tenant $tenant): bool => $user->isMemberOf($tenant));

        Gate::define('tenants.users.attach', fn (User $user, Tenant $tenant): bool => $user->isMemberOf($tenant) && ! $tenant->trashed());

        Gate::define('tenants.users.detach', fn (User $user, Tenant $tenant): bool => $user->isMemberOf($tenant));

        Gate::define('tenants.settings.view', fn (User $user, Tenant $tenant): bool => $user->isMemberOf($tenant));

        Gate::define('tenants.settings.update', fn (User $user, Tenant $tenant): bool => $user->isMemberOf($tenant));

        Gate::define('tenants.settings.delete', fn (User $user, Tenant $tenant): bool => $user->isMemberOf($tenant));
    }
}
