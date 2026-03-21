<?php

namespace App\Providers;

use Modules\Workspace\Models\Workspace;
use Modules\Workspace\Services\CurrentWorkspaceManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // TODO: Move to WorkspaceServiceProvider in Phase 5
        $this->app->scoped(CurrentWorkspaceManager::class);
    }

    public function boot(): void
    {
        // TODO: Move to WorkspaceServiceProvider in Phase 5
        // Use wildcard to match both original and module-resolved component paths
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
