<?php

namespace App\Providers;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped('current.workspace', fn () => null);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
