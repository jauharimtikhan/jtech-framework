<?php

namespace Jtech\View;

class ViewFinder
{
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function find(string $name): string
    {
        $path = $this->basePath . '/' . str_replace('.', '/', $name) . '.blade.php';

        if (!file_exists($path)) {
            throw new \Exception("View [$name] not found");
        }

        return $path;
    }
}
