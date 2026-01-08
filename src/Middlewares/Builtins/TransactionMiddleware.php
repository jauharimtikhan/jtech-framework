<?php

namespace Jtech\Middlewares\Builtins;

use Illuminate\Database\Capsule\Manager as DB;
use Jtech\Http\Middleware;
use Jtech\Http\Request;

class TransactionMiddleware implements Middleware
{
  public function handle(Request $request, \Closure $next)
  {
    DB::beginTransaction();

    try {
      $response = $next($request);
      DB::commit();
      return $response;
    } catch (\Throwable $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
