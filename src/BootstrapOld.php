<?php

namespace Jtech;

use App\Middleware\VerifyCsrfToken;
use Illuminate\Container\Container;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Str;
use Jtech\Debug\ErrorHandler;
use Jtech\Http\Request;
use Jtech\Providers\DatabaseServiceProvider;
use Jtech\Providers\PaginationServiceProvider;
use Jtech\Providers\RouteServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Jtech\Http\Response;

class BootstrapOld
{
    public function init()
    {

        $app = new Application;

        $config = [];

        foreach (glob(__DIR__ . '/../config/*.php') as $file) {
            $config[basename($file, '.php')] = require $file;
        }

        $app->singleton('config', fn() => $config);
        $app->singleton('str', fn() => new Str);
        $app->singleton('view', function () {
            return new \Jtech\View\View(
                new \Jtech\View\BladeCompiler(STORAGEPATH . '/views/cache'),
                new \Jtech\View\ViewFinder(RESOURCEPATH . '/views')
            );
        });

        $app->register(new RouteServiceProvider($app));
        return $app;
    }

    public function run($app)
    {


        try {
            $container = new Container();
            $container->singleton('config', function () {
                $items = [
                    'session' => require __DIR__ . '/../config/session.php'
                ];
                return new Repository($items);
            });
            $container->singleton('files', function () {
                return new Filesystem();
            });
            $app->register(new DatabaseServiceProvider($app));
            $app->register(new PaginationServiceProvider($app));
            // 3. Setup Session Manager
            $app->singleton('session', function ($app) use ($container) {
                return new SessionManager($container);
            });

            $app->singleton('session.store', function ($app) {
                return $app->make('session')->driver();
            });
            app('view')->getCompiler()->directive('dd', function ($expr) {
                return "<?php dd($expr); ?>";
            });

            $request = Request::createFromGlobals();
            $app->singleton('request', fn() => $request);
            app('router')->setDefaultMiddleware(function () {
                return [
                    VerifyCsrfToken::class
                ];
            });
            $response = $app->make('router')->resolve($request);
            if (\is_string($response)) {
                $content = new Response($response);
                echo $content->content();
                exit;
            }
        } catch (\Throwable $th) {
            ErrorHandler::handle($th);
        }
    }
}
