<?php

namespace Jtech\Console\Commands;

use Jtech\Console\Command;

class MakeModelCommand extends Command
{
  public function signature(): string
  {
    return 'make:model';
  }

  public function description(): string
  {
    return 'Create a new model';
  }

  public function handle(array $arguments): void
  {
    $name = $arguments[0] ?? null;

    if (!$name) {
      $this->line("Model name required.");
      return;
    }

    $path = BASEPATH . "/app/Models/{$name}.php";
    $db_name = strtolower($name);
    file_put_contents(
      $path,
      <<<PHP
<?php

namespace App\Models;

use Jtech\Database\Model;

class {$name} extends Model
{
    protected \$table = '{$db_name}s';
    protected \$keyType = 'int';
    protected \$primaryKey = 'id';
    protected \$fillable = [];
}
PHP
    );

    $this->line("Model {$name} created.");
  }
}
