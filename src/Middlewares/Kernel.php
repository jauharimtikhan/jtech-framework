<?php

namespace Jtech\Middlewares;

use ErrorException;

class Kernel
{

    public array $listMiddleware = [];
    /**
     * @param class-string $middleware
     */
    public function register(string $alias, string $middleware)
    {
        if (!class_exists($middleware)) {
            throw new ErrorException("Middleware Salah format bre");
        }
        if (!is_subclass_of($middleware, Middleware::class)) {
            throw new ErrorException("Middleware Salah ");
        }
        $this->listMiddleware[] = [
            'alias' => $alias,
            'class' => $middleware
        ];
    }
}
