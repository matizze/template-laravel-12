<?php

namespace Modules\Workspace\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Workspace\Models\Workspace;
use Modules\Workspace\Services\CurrentWorkspaceManager;

class WorkspaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentWorkspaceManager::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'workspace');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Blade::anonymousComponentPath(__DIR__.'/../../resources/components', 'workspace');

        View::composer('*', function ($view): void {
            if (! str_contains($view->name(), 'layout.dashboard')) {
                return;
            }

            $user = Auth::user();

            $view->with([
                'workspaces' => $user ? $user->workspaces : collect(),
                'currentWorkspace' => Workspace::current(),
            ]);
        });
    }
}
