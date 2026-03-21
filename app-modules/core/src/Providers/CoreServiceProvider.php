<?php

namespace Modules\Core\Providers;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register anonymous components globally (no prefix)
        Blade::anonymousComponentPath(__DIR__.'/../../resources/components');

        // Register views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'core');

        if (App::environment('local')) {
            Event::listen(
                MigrationsEnded::class,
                function () {
                    Artisan::call('ide-helper:generate');
                    Artisan::call('ide-helper:models', ['--nowrite' => true, '--reset' => true]);
                }
            );
        }
    }
}
