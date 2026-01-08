<?php

namespace Jtech;

use App\Middleware\VerifyCsrfToken;
use Dotenv\Dotenv;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Response;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Str;
use Jtech\Debug\ErrorHandler;
use Jtech\Http\Request;
use Jtech\Providers\DatabaseServiceProvider;
use Jtech\Providers\PaginationServiceProvider;
use Jtech\Providers\RouteServiceProvider;

class Bootstrap
{
    /** @var Application */
    protected $app;

    /**
     * 
     * @var Container 
     */
    protected $container;

    public function __construct()
    {
        // 1. Load Env
        (Dotenv::createImmutable(BASEPATH))->load();

        // 2. Init Application (Container)
        $this->app = new Application;
        Application::setInstance($this->app);
        $this->container = new Container;
    }

    /**
     * Load Config & Register Core Services
     */
    public function create()
    {
        // A. Load Config Files ke Repository (Biar support dot notation: config('session.driver'))
        $configItems = [];
        foreach (glob(__DIR__ . '/../config/*.php') as $file) {
            $configItems[basename($file, '.php')] = require $file;
        }

        // Bind Config sebagai Repository, bukan array biasa
        $this->app->singleton('config', fn() => $configItems);
        $this->container->singleton('config', function () use ($configItems) {

            return new Repository($configItems);
        });

        // B. Register Service Penting
        $this->registerBaseBindings();
        $this->registerCoreServices();

        return $this;
    }
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Register Route Files & Middleware
     */
    public function withRouting(callable $routePath)
    {
        // 1. Register Router Service Provider dulu
        $this->app->register(new RouteServiceProvider($this->app));

        // 3. Load File Routes
        $paths = (array) $routePath();
        foreach ($paths as $file) {
            if (file_exists($file)) {
                require_once $file;
            } else {
                throw new \Exception("File Routing Tidak Ditemukan: " . $file);
            }
        }

        return $this;
    }

    /**
     * Eksekusi Request -> Response
     */
    public function run()
    {
        try {
            // 1. Capture Request
            $request = Request::createFromGlobals();
            $this->app->singleton('request', fn() => $request);

            // 2. Resolve Route Dispatcher
            $response = $this->app->make('router')->resolve($request);

            // 3. Send Response
            $responseInstance = new Response();
            if (\is_string($response)) {
                $response = $responseInstance->setContent($response);
            }

            // Asumsi method resolve bisa return string atau object Response
            if ($response instanceof Response) {
                echo $response->content();
            } else {
                echo $response;
            }

            exit;
        } catch (\Throwable $th) {
            // Handle Error secara global
            ErrorHandler::handle($th);
        }
    }

    /**
     * Helper: Bind service dasar
     */
    protected function registerBaseBindings()
    {
        // Filesystem (Wajib untuk Session & View)
        $this->container->singleton('files', fn() => new Filesystem);

        // String Helper
        $this->app->singleton('str', fn() => new Str);
    }

    /**
     * Helper: Bind service framework utama
     */
    protected function registerCoreServices()
    {
        $this->app->singleton('response', function () {
            return new \Jtech\Http\ResponseFactory();
        });
        // 1. Database & Pagination
        $this->app->register(new DatabaseServiceProvider($this->app));
        $this->app->register(new PaginationServiceProvider($this->app));

        // 2. Session Manager
        // SessionManager butuh container yg punya 'config' (repository) dan 'files'
        $this->app->singleton('session', function ($app) {
            return new SessionManager($this->container);
        });

        $this->app->singleton('session.store', function ($app) {
            return $app->make('session')->driver();
        });

        // 3. View Engine
        $this->app->singleton('view', function () {
            return new \Jtech\View\View(
                new \Jtech\View\BladeCompiler(STORAGEPATH . '/views/cache'),
                new \Jtech\View\ViewFinder(RESOURCEPATH . '/views')
            );
        });

        // 4. Custom Blade Directives
        // Kita panggil via make biar lazy load (hanya jalan kalau view dipanggil)
        $this->app->make('view')->getCompiler()->directive('dd', function ($expr) {
            return "<?php dd($expr); ?>";
        });
    }
}
