<?php

namespace Jtech\Console\Commands;

use DatabaseSeeder;
use Jtech\Console\Command;

class SeederCommand extends Command
{
  public function signature(): string
  {
    return 'db:seed';
  }

  public function description(): string
  {
    return 'Seeder data';
  }

  public function handle(array $arguments): void
  {
    (new DatabaseSeeder())->run();
    $this->line("Seeder berhasil dijalan kan!");
  }
}
