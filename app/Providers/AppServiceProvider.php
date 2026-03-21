<?php

namespace App\Providers;

use App\Models\Workspace;
use App\Services\CurrentWorkspaceManager;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra os serviços da aplicação.
     */
    public function register(): void
    {
        // Registra o gerenciador de workspace como scoped para reset automatico entre requests no Octane
        $this->app->scoped(CurrentWorkspaceManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Compartilha dados de workspace com o layout do dashboard
        View::composer('components.layout.dashboard', function ($view): void {
            $user = Auth::user();
            $workspaces = $user ? $user->workspaces : collect();
            $currentWorkspace = Workspace::current();

            $view->with('workspaces', $workspaces);
            $view->with('currentWorkspace', $currentWorkspace);
        });

        if (! App::environment('local')) {
            return;
        }

        // Intercepta as migrations para gerar os ide_helpers
        Event::listen(
            MigrationsEnded::class,
            function () {
                Artisan::call('ide-helper:generate');
                Artisan::call('ide-helper:models', ['--nowrite' => true, '--reset' => true]);
            }
        );
    }
}
