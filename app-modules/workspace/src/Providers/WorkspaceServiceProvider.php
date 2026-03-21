<?php

namespace Modules\Workspace\Providers;

use Modules\Workspace\Models\Workspace;
use Modules\Workspace\Services\CurrentWorkspaceManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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

        // Register workspace components with prefix: <x-workspace::*>
        Blade::anonymousComponentPath(__DIR__.'/../../resources/components', 'workspace');

        // Share workspace data with dashboard layout
        View::composer('*', function ($view): void {
            $viewName = $view->name();
            if (! str_contains($viewName, 'layout.dashboard')) {
                return;
            }

            $user = Auth::user();
            $workspaces = $user ? $user->workspaces : collect();
            $currentWorkspace = Workspace::current();

            $view->with('workspaces', $workspaces);
            $view->with('currentWorkspace', $currentWorkspace);
        });
    }
}
