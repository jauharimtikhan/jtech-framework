<?php

namespace Jtech\Http;

use Exception;
use Jtech\Database\Model;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionFunctionAbstract;

class Router
{
    protected static array $routes = [];
    protected static array $groupStack = [];

    // Middleware
    protected static array $defaultMiddleware = [];
    protected static array $excludedMiddleware = []; // [New] Penampung exclusion

    protected static array $pendingRoute = [
        'method' => null,
        'uri' => null,
        'action' => null,
        'name' => null,
        'middleware' => [],
    ];

    /* =========================
     | HTTP METHODS
     ========================= */

    public static function get(string $uri, string|callable|array $action)
    {
        return static::addRoute('GET', $uri, $action);
    }

    public static function post(string $uri, string|callable|array $action)
    {
        return static::addRoute('POST', $uri, $action);
    }

    public static function put(string $uri, string|callable|array $action)
    {
        return static::addRoute('PUT', $uri, $action);
    }

    public static function delete(string $uri, string|callable|array $action)
    {
        return static::addRoute('DELETE', $uri, $action);
    }

    protected static function addRoute($method, $uri, $action)
    {
        static::prepareRoute($method, $uri, $action);
        return new static;
    }

    /* =========================
     | GROUPING
     ========================= */

    public static function group(array $attributes, callable $callback)
    {
        static::$groupStack[] = [
            'prefix'     => $attributes['prefix'] ?? '',
            'middleware' => (array) ($attributes['middleware'] ?? []),
            'controller' => $attributes['controller'] ?? null,
            'name'       => $attributes['name'] ?? '',
        ];

        $callback();

        array_pop(static::$groupStack);
    }

    /* =========================
     | ROUTE BUILDING
     ========================= */

    protected static function prepareRoute(string $method, string $uri, $action)
    {
        $controller = static::resolveController();

        // Auto-prefix controller method string
        if (is_string($action) && !str_contains($action, '@') && $controller) {
            $action = $controller . '@' . $action;
        }

        static::$pendingRoute = [
            'method'     => $method,
            'uri'        => static::resolvePrefix($uri),
            'action'     => $action,
            'name'       => null,
            'middleware' => [],
        ];
    }

    public function middleware(string|array $middleware)
    {
        static::$pendingRoute['middleware'] = (array) $middleware;
        return $this;
    }

    public function name(string $name)
    {
        static::$pendingRoute['name'] = static::resolveName($name);
        static::commitRoute();
        return $this;
    }

    protected static function commitRoute()
    {
        // Gabungkan middleware dari Group + Route saat ini
        static::$pendingRoute['middleware'] =
            static::resolveMiddleware(static::$pendingRoute['middleware']);

        static::$routes[] = static::$pendingRoute;

        // Reset
        static::$pendingRoute = [
            'method' => null,
            'uri' => null,
            'action' => null,
            'name' => null,
            'middleware' => []
        ];
    }

    /* =========================
     | MIDDLEWARE MANAGEMENT (UPDATED)
     ========================= */

    public function setDefaultMiddleware(callable $func)
    {
        static::$defaultMiddleware = (array) $func();
    }

    /**
     * Exclude specific routes (by URI or Name) from global middleware
     * Usage: Router::excludeRouteFromMiddleware('/login', VerifyCsrfToken::class);
     */
    public static function excludeRouteFromMiddleware(string|array $routes, string|array $middleware)
    {
        $routes = (array) $routes;
        $middleware = (array) $middleware;

        foreach ($routes as $routeIdentifier) {
            // Normalisasi identifier (misal remove slash depan)
            $key = $routeIdentifier;
            if (str_starts_with($key, '/')) {
                $key = '/' . ltrim($key, '/');
            }

            if (!isset(static::$excludedMiddleware[$key])) {
                static::$excludedMiddleware[$key] = [];
            }

            static::$excludedMiddleware[$key] = array_merge(
                static::$excludedMiddleware[$key],
                $middleware
            );
        }
    }

    protected static function getFinalMiddleware(array $route)
    {
        // 1. Gabung Default + Route Middleware
        $middleware = array_merge(static::$defaultMiddleware, $route['middleware']);

        // 2. Cek Exclusion berdasarkan URI
        $uriKey = '/' . ltrim($route['uri'], '/');
        $excluded = static::$excludedMiddleware[$uriKey] ?? [];

        // 3. Cek Exclusion berdasarkan Name (jika ada)
        if (!empty($route['name'])) {
            $excluded = array_merge($excluded, static::$excludedMiddleware[$route['name']] ?? []);
        }

        // 4. Filter middleware yang di-exclude
        if (!empty($excluded)) {
            $middleware = array_diff($middleware, $excluded);
        }

        return array_unique($middleware);
    }

    /* =========================
     | RESOLVERS
     ========================= */

    protected static function resolvePrefix(string $uri): string
    {
        $prefix = '';
        foreach (static::$groupStack as $group) {
            $prefix .= '/' . trim($group['prefix'], '/');
        }
        return rtrim($prefix . '/' . trim($uri, '/'), '/') ?: '/';
    }

    protected static function resolveController(): ?string
    {
        // Loop backward
        for ($i = count(static::$groupStack) - 1; $i >= 0; $i--) {
            if (!empty(static::$groupStack[$i]['controller'])) {
                return static::$groupStack[$i]['controller'];
            }
        }
        return null;
    }

    protected static function resolveName(string $name): string
    {
        $prefix = '';
        foreach (static::$groupStack as $group) {
            $prefix .= $group['name'] ?? '';
        }
        return $prefix . $name;
    }

    protected static function resolveMiddleware(array $routeMiddleware): array
    {
        $middleware = [];
        foreach (static::$groupStack as $group) {
            $middleware = array_merge($middleware, $group['middleware']);
        }
        return array_merge($middleware, $routeMiddleware);
    }

    /* =========================
     | ROUTE RESOLUTION
     ========================= */

    public static function resolve($request)
    {
        $uri = $request->uri()->path();
        $method = $request->method();
        $uriMatched = false;

        foreach (static::$routes as $route) {
            // Match Logic
            $params = match_routes(ltrim($route['uri'], '/'), ltrim($uri, '/'));

            if ($params === false) continue;

            $uriMatched = true;

            if ($route['method'] !== $method) continue;

            return static::runRoute($route, $request, $params);
        }

        if ($uriMatched) {
            http_response_code(405);
            throw new Exception('Method Not Allowed');
        }

        http_response_code(404);
        throw new Exception('Route Not Found');
    }

    protected static function runRoute(array $route, $request, array $params)
    {
        // 1. Normalize Action to Callable
        $action = $route['action'];
        $callable = null;
        $reflector = null;

        if (is_callable($action)) {
            // Case: Closure
            $callable = $action;
            $reflector = new ReflectionFunction($action);
        } elseif (is_array($action)) {
            // Case: [Controller::class, 'method']
            $controller = new $action[0];
            $method = $action[1];
            $callable = [$controller, $method];
            $reflector = new ReflectionMethod($controller, $method);
        } elseif (is_string($action) && str_contains($action, '@')) {
            // Case: "Controller@method"
            [$controllerName, $method] = explode('@', $action);

            $controllerClass = class_exists($controllerName) ? $controllerName : "App\\Controllers\\{$controllerName}";

            if (!class_exists($controllerClass)) {
                throw new Exception("Controller [$controllerClass] not found");
            }

            $controller = new $controllerClass;
            $callable = [$controller, $method];
            $reflector = new ReflectionMethod($controller, $method);
        } else {
            throw new Exception('Invalid route action');
        }

        // 2. Resolve Dependencies (DRY Implementation)
        // Kita pake satu method helper buat resolve parameter, baik itu Closure maupun Controller
        $arguments = static::resolveDependencies($reflector, $request, $params);

        // 3. Prepare Core Execution
        $core = fn() => call_user_func_array($callable, $arguments);

        // 4. Wrap with Middleware (Included Exclusion Logic)
        $middleware = static::getFinalMiddleware($route);

        foreach (array_reverse($middleware) as $middlewareClass) {
            $next = $core;
            $core = fn() => (new $middlewareClass)->handle($request, $next);
        }

        return $core();
    }

    /* =========================
     | DEPENDENCY RESOLVER (OPTIMIZED)
     ========================= */

    protected static function resolveDependencies(ReflectionFunctionAbstract $reflector, $request, array $params): array
    {
        $arguments = [];

        foreach ($reflector->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            // A. Inject Request Object
            if ($type && !$type->isBuiltIn() && $type->getName() === \Jtech\Http\Request::class) {
                $arguments[] = $request;
                continue;
            }

            // B. Route Model Binding
            if ($type && !$type->isBuiltIn()) {
                $className = $type->getName();
                if (is_subclass_of($className, Model::class)) {
                    if (array_key_exists($name, $params)) {
                        $id = $params[$name];
                        $modelInstance = $className::find($id);

                        if (!$modelInstance) {
                            if ($parameter->isDefaultValueAvailable()) {
                                $arguments[] = $parameter->getDefaultValue();
                                continue;
                            }
                            throw new Exception("No query results for model [{$className}] $id");
                        }

                        $arguments[] = $modelInstance;
                        continue;
                    }
                }
            }

            // C. Primitive/URL Parameters
            if (array_key_exists($name, $params)) {
                $arguments[] = $params[$name];
                continue;
            }

            // D. Default Value
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new Exception("Cannot resolve parameter [$name] in " . $reflector->getName());
        }

        return $arguments;
    }

    public static function getRoutes()
    {
        return static::$routes;
    }
}
