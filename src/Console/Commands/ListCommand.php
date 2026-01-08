<?php

namespace Jtech\Console\Commands;

use Jtech\Console\Command;

class ListCommand extends Command
{
  public function signature(): string
  {
    return 'list';
  }

  public function description(): string
  {
    return 'List all available commands';
  }

  public function handle(array $arguments): void
  {
    $this->line("Available commands:");
    foreach (glob(__DIR__ . "/*.php") as $file) {
      $newClass = "Jtech\Console\Commands\\" . basename($file, '.php');
      $reflection = new $newClass();
      $signature = $reflection->signature();
      $description = $reflection->description();
      $this->line("$signature | $description");
    }
  }
}
