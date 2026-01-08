<?php

namespace Jtech\Database;

abstract class Seeder
{
  abstract public function run();

  public function call(array $seeders)
  {
    foreach ($seeders as $seeder) {
      (new $seeder)->run();
    }
  }
}
