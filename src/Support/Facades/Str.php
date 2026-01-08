<?php

namespace Jtech\Support\Facades;

use Jtech\Support\Facade;

class Str extends Facade
{
  protected static function getFacadeAccessor(): string
  {
    return 'str';
  }
}
