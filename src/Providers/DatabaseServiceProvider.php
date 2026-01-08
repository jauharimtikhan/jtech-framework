<?php

namespace Jtech\Providers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container as IlluminateContainer;
use Jtech\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
  public function register()
  {
    $this->app->singleton('db', function ($app) {

      $dbConfig = $app->make('config')['database'];

      $capsule = new Capsule;

      $capsule->addConnection(
        $dbConfig['connections'][$dbConfig['default']]
      );

      $capsule->setEventDispatcher(
        new Dispatcher(new IlluminateContainer)
      );

      $capsule->setAsGlobal();   // DB::table()
      $capsule->bootEloquent();  // Model::query()
      ValidationServiceProvider::register($this->app, $capsule);
      return $capsule;
    });
  }
}
