<?php

namespace Modules\Permission\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Permission\Services\PermissionService;
use Modules\User\Models\User;

class PermissionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Gate::before(function (?User $user, string $ability): ?bool {
            if ($user === null) {
                return null;
            }

            return app(PermissionService::class)->check($user, $ability) ? true : null;
        });
    }
}
