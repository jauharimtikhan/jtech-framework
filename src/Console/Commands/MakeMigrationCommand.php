<?php

namespace Jtech\Console\Commands;

use Jtech\Console\Command;

class MakeMigrationCommand extends Command
{
  public function signature(): string
  {
    return 'make:migration';
  }

  public function description(): string
  {
    return 'Create a new migration file';
  }

  public function handle(array $arguments): void
  {
    $name = $arguments[0] ?? null;

    if (! $name) {
      $this->line('Migration name required.');
      $this->line('Example: make:migration create_users_table');
      return;
    }

    $timestamp = date('Y_m_d_His');
    $fileName  = "{$timestamp}_{$name}.php";
    $path      = BASEPATH . "/database/migrations/{$fileName}";

    if (file_exists($path)) {
      $this->line("Migration already exists.");
      return;
    }

    file_put_contents($path, $this->stub($name));

    $this->line("Migration created: {$fileName}");
  }

  protected function stub(string $name): string
  {
    $className = $this->className($name);

    return <<<PHP
<?php

use Jtech\Database\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::create('{$name}', function (Blueprint \$table) {
        //     \$table->id();
        //     \$table->timestamps();
        // });
    }

    public function down(): void
    {
        // Schema::dropIfExists('{$name}');
    }
};
PHP;
  }

  protected function className(string $name): string
  {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
  }
}
