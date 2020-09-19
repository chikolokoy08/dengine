<?php

namespace Cmabugay\Dengine;

use Illuminate\Support\ServiceProvider;

class DengineServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton('dengine', function ($app) {
            return new Dengine($app['request']->server());
        });

        $this->app->alias('dengine', Dengine::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['dengine', Dengine::class];
    }
}
