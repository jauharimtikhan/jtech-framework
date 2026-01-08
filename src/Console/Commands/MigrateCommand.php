<?php

namespace Jtech\Console\Commands;

use Jtech\Console\Command;
use Jtech\Support\Facades\DB;

class MigrateCommand extends Command
{
  public function signature(): string
  {
    return 'migrate';
  }

  public function description(): string
  {
    return 'Migrate database';
  }
  public function handle(array $arguments): void
  {
    $ran = DB::table('migrations')->pluck('migration')->toArray();

    foreach (glob(BASEPATH . '/database/migrations/*.php') as $file) {
      $name = basename($file);

      if (in_array($name, $ran)) {
        continue;
      }

      $migration = require $file;
      $migration->up();

      DB::table('migrations')->insert([
        'migration' => $name,
        'ran_at' => date('Y-m-d H:i:s'),
      ]);

      $this->line("Migrasi berhasil!");
    }
  }

  protected function allMigrationFiles(): array
  {
    return glob(BASEPATH . '/database/migrations/*.php');
  }

  protected function migrated(): array
  {
    return app()->make('db')
      ->table('migrations')
      ->pluck('migration')
      ->toArray();
  }

  protected function log(string $file)
  {
    app()->make('db')->table('migrations')->insert([
      'migration' => basename($file),
      'ran_at' => now(),
    ]);
  }

  protected function removeLog(string $file)
  {
    app()->make('db')
      ->table('migrations')
      ->where('migration', basename($file))
      ->delete();
  }
}
