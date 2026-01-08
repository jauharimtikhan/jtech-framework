<?php

namespace Jtech\Support;

abstract class Facade
{
  protected static function getFacadeAccessor(): string
  {
    throw new \Exception('Facade accessor not implemented.');
  }

  protected static function resolveInstance()
  {

    return app()->make(static::getFacadeAccessor());
  }

  public static function __callStatic($method, $arguments)
  {
    $instance = static::resolveInstance();

    if (!method_exists($instance, $method)) {
      throw new \Exception(
        "Method [$method] does not exist on facade accessor."
      );
    }

    return $instance->$method(...$arguments);
  }
}
