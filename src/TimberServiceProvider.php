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
        $this->publishes([
            __DIR__ . '/config/timber.php' => config_path(static::$abstract . '.php'),
        ], 'config');
    }

    public function register()
    {
        $ver = explode(".",$this->app->version());
		if($ver[0] == "5" && (int)$ver[1] < 6){
			$this->app->configureMonologUsing(function ($monolog)
			{
				$monolog->pushHandler(new TimberLaravelHandler());
			});
		}

        $this->app->bind(\Liteweb\TimberApi\TimberApi::class, function ($app)
        {
            $tApi = new \Liteweb\TimberApi\TimberApi();
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
