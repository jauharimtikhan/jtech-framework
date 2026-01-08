<?php

namespace Jtech;

use Illuminate\Container\Container as IlluminateContainer;

class Container
{
  protected array $bindings = [];
  protected array $instances = [];

  public function bind(string $key, callable $resolver)
  {
    $this->bindings[$key] = $resolver;
  }

  public function singleton(string $key, callable $resolver)
  {
    $this->instances[$key] = $resolver($this);
  }

  public function make(string $key)
  {
    if (isset($this->instances[$key])) {
      return $this->instances[$key];
    }

    if (!isset($this->bindings[$key])) {
      throw new \Exception("Service [$key] not bound.");
    }

    return $this->bindings[$key]($this);
  }

  public static function getIlluminateContainer()
  {
    return new IlluminateContainer;
  }
}
