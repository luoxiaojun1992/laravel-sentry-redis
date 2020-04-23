<?php

namespace Lxj\Laravel\Sentry;

use Illuminate\Support\ServiceProvider;
use Lxj\Laravel\Sentry\Commands\SentryTransport;
use Sentry\SentryLaravel\SentryLaravelServiceProvider;

class Provider extends ServiceProvider
{
    public function register()
    {
        parent::register();

        $this->mergeConfigFrom(
            __DIR__ . '/config/sentry.php',
            SentryLaravelServiceProvider::$abstract
        );
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->commands([
            SentryTransport::class,
        ]);
    }
}
