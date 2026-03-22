<?php

namespace Modules\Workspace\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\User\Models\User;
use Modules\Workspace\Enums\WorkspaceRole;
use Modules\Workspace\Models\Member;
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

        $this->registerUserRelationships();

        View::composer('*layout.dashboard*', static function ($view): void {
            $user = Auth::user();

            $view->with([
                /** @phpstan-ignore method.notFound (dynamic relation via resolveRelationUsing) */
                'workspaces' => $user ? $user->workspaces()->get() : collect(),
                'currentWorkspace' => Workspace::current(),
            ]);
        });
    }

    private function registerUserRelationships(): void
    {
        User::resolveRelationUsing('workspaces', function (User $user) {
            return $user->belongsToMany(Workspace::class, 'members')
                ->using(Member::class)
                ->withPivot('id', 'role')
                ->withCasts(['role' => WorkspaceRole::class])
                ->withTimestamps();
        });
    }
}
