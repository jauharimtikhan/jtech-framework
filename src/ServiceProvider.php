<?php

namespace Jtech;

abstract class ServiceProvider
{
  protected Container $app;

  public function __construct(Container $app)
  {
    $this->app = $app;
  }

  public function register() {}
  public function boot() {}
}
