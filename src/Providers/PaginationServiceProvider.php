<?php

namespace Jtech\Providers;

use Illuminate\Pagination\Paginator;
use Jtech\ServiceProvider;

class PaginationServiceProvider extends ServiceProvider
{
  public function register()
  {
    Paginator::currentPageResolver(function () {
      return (int) ($_GET['page'] ?? 1);
    });

    Paginator::currentPathResolver(function () {
      return strtok($_SERVER['REQUEST_URI'], '?');
    });
  }
}
