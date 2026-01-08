<?php

namespace Jtech\Console;

abstract class Command
{
  abstract public function signature(): string;
  abstract public function description(): string;
  abstract public function handle(array $arguments): void;

  protected function line(string $text)
  {
    echo $text . PHP_EOL;
  }
}
