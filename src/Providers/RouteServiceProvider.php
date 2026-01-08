<?php

namespace Jtech\Providers;

use Jtech\Http\Router;
use Jtech\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
  public function register()
  {
    $this->app->singleton('router', fn() => new Router);
  }

  public function boot() {}
}
