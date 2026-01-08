<?php

namespace Jtech\Console;

class Kernel
{
  protected array $commands = [];

  public function __construct()
  {
    $this->registerDefaultCommands();
  }

  protected function registerDefaultCommands()
  {
    foreach (glob(__DIR__ . '/Commands/*.php') as $file) {
      $class = 'Jtech\\Console\\Commands\\' . basename($file, '.php');
      $this->register(new $class);
    }
  }

  public function register(Command $command)
  {
    $this->commands[$command->signature()] = $command;
  }

  public function run(array $argv)
  {
    $commandName = $argv[1] ?? 'list';

    if (!isset($this->commands[$commandName])) {
      echo "Command [$commandName] not found.\n";
      exit(1);
    }

    $this->commands[$commandName]->handle(array_slice($argv, 2));
  }
}
