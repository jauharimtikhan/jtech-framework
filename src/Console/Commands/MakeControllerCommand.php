<?php

namespace Jtech\Console\Commands;

use Jtech\Console\Command;

class MakeControllerCommand extends Command
{
  public function signature(): string
  {
    return 'make:controller';
  }

  public function description(): string
  {
    return 'Create a new controller';
  }

  public function handle(array $arguments): void
  {
    $name = $arguments[0] ?? null;

    if (!$name) {
      $this->line("Controller name required.");
      return;
    }

    $path = BASEPATH . "/app/Controllers/{$name}.php";
    file_put_contents(
      $path,
      <<<PHP
<?php

namespace App\Controllers;

use Jtech\Http\Request;

class {$name} extends Controller
{
  public function __construct()
  {
      // 
  }
}
PHP
    );

    $this->line("Controller {$name} created.");
  }
}
