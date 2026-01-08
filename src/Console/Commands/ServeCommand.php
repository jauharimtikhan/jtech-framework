<?php

namespace Jtech\Console\Commands;

use Jtech\Console\Command;

class ServeCommand extends Command
{
  public function signature(): string
  {
    return 'serve';
  }

  public function description(): string
  {
    return 'enable dev server';
  }

  public function handle(array $arguments): void
  {
    exec('php -S localhost:8000 -t public');
    $this->line('Development server started in url: http://localhost:8000');
  }
}
