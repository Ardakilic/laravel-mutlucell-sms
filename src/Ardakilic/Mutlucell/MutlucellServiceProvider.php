<?php

namespace Ardakilic\Mutlucell;

/**
 * Laravel 11 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <arda@kilicdagi.com>
 * @link https://arda.pw
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
    // The configuration file to be shared
    $this->publishes([
      __DIR__ . '/../../config/mutlucell.php' => config_path('mutlucell.php'),
    ], 'config');

    // The translations file to be shared
    $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'mutlucell');
  }

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register()
  {
    $this->app->singleton('mutlucell', function ($app) {
      return new Mutlucell($app);
    });
  }

  /**
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides()
  {
    return ['mutlucell'];
  }
}
