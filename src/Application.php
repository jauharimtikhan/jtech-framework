<?php

namespace Jtech;

class Application extends Container
{
    protected array $providers = [];
    protected static $instance;

    public function register(ServiceProvider $provider)
    {
        $provider->register();
        $this->providers[] = $provider;
    }

    public function boot()
    {
        foreach ($this->providers as $provider) {
            $provider->boot();
        }
    }
    public static function setInstance($container)
    {
        static::$instance = $container;
    }

    // 3. Getter: Dipanggil sama helper app()
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            // Opsional: Error handling kalau container belum dibuat
            throw new \Exception("Application instance belum di-set, bre!");
        }
        return static::$instance;
    }
}
