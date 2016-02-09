<?php
namespace Ardakilic\Mutlucell;

/**
 * Laravel 5 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <arda@kilicdagi.com>
 * @link http://arda.pw
 *
 */

use Illuminate\Support\ServiceProvider;

class MutlucellServiceProvider extends ServiceProvider
{

    /**
     * @var bool $defer Indicates if loading of the provider is deferred.
     */
    protected $defer = false;

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        //paylaşılacak config dosyası
        $this->publishes([
            __DIR__ . '/../../config/mutlucell.php' => config_path('mutlucell.php'),
        ], 'config');

        //paylaşılacak dil dosyaları
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'mutlucell');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //register edelim
        $this->app['mutlucell'] = $this->app->share(function ($app) {
            return new Mutlucell($app);
        });

    }

    /**
     * Get the services provided by the provider.
     *
     * @return string
     */
    public function provides()
    {
        return ['mutlucell'];
    }


}