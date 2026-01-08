<?php

namespace Jtech\Http;

interface Middleware
{
  public function handle(Request $request, \Closure $next);
}
