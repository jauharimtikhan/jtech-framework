<?php

use Illuminate\Session\SessionManager;
use Jtech\Application;
use Jtech\Debug\HttpException;
use Jtech\Http\Response;
use Jtech\Support\Facades\Route;

use function Illuminate\Support\now as SupportNow;

if (!function_exists('env')) {
    function env(string $key = '')
    {
        if (empty($key) && isset($key) && $key === '') {
            return $_ENV;
        } else {
            return $_ENV[$key];
        }
    }
}


if (!function_exists('app')) {
    function app(?string $abstract = null)
    {
        $container = Application::getInstance();

        if (is_null($abstract)) {
            return $container;
        }

        // 2. Pastikan container lo punya method 'make'
        return $container->make($abstract);
    }
}
if (!function_exists('match_routes')) {
    function match_routes(string $routePattern, string $requestUri)
    {
        $regex = preg_replace(
            '/\{([a-zA-Z0-9_]+)\}/',
            '(?P<$1>[^/]+)',
            $routePattern
        );

        $regex = "#^" . rtrim($regex, '/') . "$#";

        if (preg_match($regex, rtrim($requestUri, '/'), $matches)) {
            return array_filter(
                $matches,
                fn($key) => !is_numeric($key),
                ARRAY_FILTER_USE_KEY
            );
        }

        return false;
    }
}

if (!function_exists('config')) {
    function config(?string $key = null)
    {
        if (isset($key)) {
            return data_get(app()->make('config'), $key);
        }
        return app()->make('config');
    }
}

if (!function_exists('request')) {
    function request()
    {
        return app('request');
    }
}



if (!function_exists('data_get')) {
    function data_get($target, ?string $key = null, $default = null)
    {
        if ($key === null) {
            return $target;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }
}


if (!function_exists('now')) {
    function now()
    {
        return SupportNow('Asia/Jakarta');
    }
}


if (!function_exists('session')) {
    function session(): SessionManager
    {
        return app('session');
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = [])
    {
        return app('view')->render($name, $data);
    }
}

if (!function_exists('base_url')) {

    function base_url($path = '')
    {
        // 1. Cek konfigurasi APP_URL di .env (Prioritas Utama)
        // Pastikan lo udah load .env sebelumnya
        $appUrl = $_ENV['APP_URL'] ?? null;

        // 2. Jika tidak ada di .env, lakukan Auto-Detection (Fallback)
        if (!$appUrl) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];

            // Ambil folder project (misal: /j-tech/public)
            // dirname($_SERVER['SCRIPT_NAME']) akan mengembalikan path ke folder index.php berada
            $scriptDir = dirname($_SERVER['SCRIPT_NAME']);

            // Normalisasi slash (Windows pakai backslash \, kita ubah ke forward slash /)
            $scriptDir = str_replace('\\', '/', $scriptDir);

            // Hapus slash di akhir jika ada (biar konsisten)
            $scriptDir = rtrim($scriptDir, '/');

            $appUrl = "{$protocol}://{$host}{$scriptDir}";
        }

        // 3. Gabungkan Base URL dengan Path yang diminta
        // Bersihkan slash ganda di sambungan
        return rtrim($appUrl, '/') . '/' . ltrim($path, '/');
    }
}

// BONUS: Helper asset() biar kodingan view makin rapi
if (!function_exists('asset')) {
    function asset($path)
    {
        $path = "storage/$path";
        return base_url($path);
    }
}

if (!function_exists('route')) {
    function route(string $key): ?string
    {
        $routes = Route::getRoutes();
        $current = "";
        foreach ($routes as  $route) {
            if ($route['name'] === $key) {
                $current .= base_url($route['uri']);
            }
        }
        return $current;
    }
}


if (!function_exists('old')) {
    function old(string $key, $default = null)
    {
        return session()->get('_old_input')[$key] ?? $default;
    }
}

if (!function_exists('errors')) {
    function errors()
    {
        return session()->get('errors');
    }
}

if (!function_exists('response')) {
    /**
     * Return response factory or implementation
     *
     * @return \Illuminate\Http\Response|\Jtech\Http\ResponseFactory
     */
    function response($content = '', $status = 200, array $headers = [])
    {
        $factory = app('response');

        // Kalau argumen kosong, return Factory biar bisa di-chain (response()->json())
        if (func_num_args() === 0) {
            return $factory;
        }

        // Kalau ada isi, langsung return object Response standar
        return $factory->make($content, $status, $headers);
    }
}

// Tambahan Helper Redirect biar makin enak
if (!function_exists('redirect')) {
    function redirect($to = null, $status = 302, $headers = [])
    {
        $factory = app('response');

        if (is_null($to)) {
            return $factory; // Biar bisa redirect()->to(...) kalau mau dikembangin
        }

        return $factory->redirectTo($to, $status, $headers);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        $session = session();

        if (!$session->get('_token')) {
            $session->put('_token', bin2hex(random_bytes(32)));
        }

        return $session->get('_token');
    }
}


if (!function_exists('abort')) {
    function abort(int $code, string $message = '')
    {
        throw new HttpException($code, $message);
    }
}
