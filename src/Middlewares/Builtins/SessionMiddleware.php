<?php

namespace Jtech\Middlewares\Builtins;

use Jtech\Http\Request;
use Jtech\Middlewares\Middleware;

class SessionMiddleware extends Middleware
{
    protected $session;

    public function __construct()
    {
        // Ambil session store dari container global / helper
        $this->session = app('session.store');
    }

    public function handle(Request $request, callable $next)
    {
        $cookieName = $this->session->getName();

        // 2. Cek apakah Browser ngirim cookie session id?
        // Gunakan $_COOKIE native atau $request->cookies->get() kalau ada
        $sessionID = $_COOKIE[$cookieName] ?? null;

        // 3. PENTING: Kalau ada ID, paksa session pake ID itu!
        if ($sessionID) {
            // Kalau cookie lo di-encrypt (config session.encrypt = true),
            // Lo harus decrypt dulu disini pake App('encrypter')->decrypt($sessionID).
            // Tapi kalau false (default), langsung set aja.
            $this->session->setId($sessionID);
        }

        // 4. Baru di-start
        $this->session->start();

        // ... lanjut ke controller ...
        $response = $next($request);

        // 5. Save & Kirim Cookie balik (Refresh umur cookie)
        $this->session->save();
        $this->addCookieToResponse($response);

        return $response;
    }

    protected function addCookieToResponse($response)
    {
        $config = config('session');

        $cookie = setcookie(
            $this->session->getName(),
            $this->session->getId(),
            time() + ($config['lifetime'] * 60),
            $config['path'],
            $config['domain'] ?? '',
            $config['secure'],
            $config['http_only']
        );

        // Kalau framework lo punya object Response yang proper,
        // attach cookie-nya ke object itu, bukan pake setcookie() native php.
    }
}
