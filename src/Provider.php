<?php

namespace Lxj\Laravel\Sentry;

use Illuminate\Support\ServiceProvider;
use Lxj\Laravel\Sentry\Commands\SentryTransport;

class Provider extends ServiceProvider
{
    public function register()
    {
        parent::register();
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
