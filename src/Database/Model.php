<?php

namespace Jtech\Database;

use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class Model extends Eloquent
{
  protected $guarded = [];
}
