<?php

namespace Jtech\Support\Facades;

use Jtech\Support\Facade;

class DB extends Facade
{
  protected static function getFacadeAccessor(): string
  {
    return 'db';
  }
}
