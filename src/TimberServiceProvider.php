<?php

namespace Liteweb\TimberLaravel;

use Illuminate\Support\ServiceProvider;

class TimberServiceProvider extends ServiceProvider
{
    /**
     * Abstract type to bind Timber in the Service Container.
     *
     * @var string
     */
    public static $abstract = 'timber';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole())
        {
            $this->publishes([
                __DIR__ . '/../config/timber.php' => config_path(static::$abstract . '.php'),
            ], 'config');
        }
    }

    public function register()
    {
        $this->app->configureMonologUsing(function ($monolog)
        {
            $monolog->pushHandler(new \Liteweb\Timber\TimberLaravel\TimberLaravelHandler());
        });

        $this->app->bind(\Liteweb\Timber\TimberApi\TimberApi::class, function ($app)
        {
            $tApi = new \Liteweb\Timber\TimberApi\TimberApi();
            $tApi->setAuthToken($app['config']['timber']['api_key']);

            return $tApi;
        });

        $this->app->singleton(static::$abstract, function ($app)
        {
            return new TimberLaravel();
        });
    }

    public function provides()
    {
        return [static::$abstract];
    }
}
