<?php

namespace Jtech\Middlewares\Builtins;

use Jtech\Http\Request;
use Jtech\Middlewares\Middleware;

class VerifyCsrfToken extends Middleware
{
    public function handle(Request $request, \Closure $next)
    {
        if ($request->method() === 'POST') {
            $token = $request->input('_token');

            if ($token !== session()->get('_token')) {
                throw new \Exception('CSRF token mismatch');
            }
        }

        return $next($request);
    }
}
