<?php

namespace Jtech\Support\Facades;

use Jtech\Support\Facade;

class Route extends Facade
{
  protected static function getFacadeAccessor(): string
  {
    return 'router';
  }
}
