<?php

namespace Jtech\Debug;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorHandler
{
  public static function handle(\Throwable $e)
  {
    if ($e instanceof \Illuminate\Validation\ValidationException) {
      return static::handleValidation($e);
    }


    $env = $_ENV['APP_ENV'] ?? 'local';
    $debug = $_ENV['APP_DEBUG'] ?? true;

    $statusCode = self::getStatusCode($e);

    if (ob_get_level()) ob_clean();

    http_response_code($statusCode);

    if ($env === 'production' && !$debug) {
      self::renderProductionError($statusCode, $e);
    } else {
      self::renderDebugError($e);
    }

    exit;
  }

  protected static function handleValidation(\Illuminate\Validation\ValidationException $e)
  {
    $errors = $e->validator->errors();
    $request = request();

    if ($request->wantsJson() || $request->isJson()) {

      response()->json([
        'message' => 'The given data was invalid.', // Standar pesan error
        'errors'  => $errors
      ], 422)->send();

      exit;
    }

    session()->flash('errors', $errors);
    session()->flash('_old_input', $request->all());

    session()->save();

    $back = $_SERVER['HTTP_REFERER'] ?? base_url('/');

    header('Location: ' . $back);
    exit;
  }

  private static function renderProductionError($code, \Throwable $e)
  {
    $titles = [
      401 => 'Unauthorized',
      404 => 'Page Not Found',
      405 => 'Method Not Allowed',
      409 => 'Conflict',
      500 => 'Internal Server Error',
      502 => 'Bad Gateway',
    ];

    $messages = [
      401 => 'Maaf, akses ditolak. Kamu tidak memiliki izin ke sini.',
      404 => 'Waduh! Halaman yang kamu cari entah kemana.',
      405 => 'Metode request HTTP tidak diizinkan untuk rute ini.',
      409 => 'Terjadi konflik pada request data kamu.',
      500 => 'Ada masalah di server kami. Coba refresh sebentar lagi ya.',
      502 => 'Server sedang sibuk atau menerima respon buruk dari upstream.',
    ];

    // Data untuk View
    $title   = $titles[$code] ?? 'Error';
    $message = $messages[$code] ?? 'Terjadi kesalahan yang tidak terduga.';

    // Load View Production
    if (defined('RESOURCEPATH')) {
      $viewCustom = RESOURCEPATH;
    } elseif (defined('BASEPATH')) {
      $viewCustom = BASEPATH . '/resources';
    } else {
      // Fallback untuk unit testing atau CLI jika konstanta belum define
      $viewCustom = getcwd() . '/resources';
    }
    $viewCustom = RESOURCEPATH . "/views/errors/$code.blade.php";
    if (file_exists($viewCustom)) {
      require $viewCustom;
    } else {
      require __DIR__ . '/views/production_error.php';
    }
  }

  private static function renderDebugError(\Throwable $e)
  {
    $error = [
      'message' => $e->getMessage(),
      'file'    => $e->getFile(),
      'line'    => $e->getLine(),
      'trace'   => $e->getTrace(),
      'class'   => get_class($e),
    ];
    $codePreview = self::getCodePreview($error['file'], $error['line']);
    extract($error);
    extract(['codePreview' => $codePreview]);
    require __DIR__ . '/views/debug.php';
  }

  private static function getStatusCode(\Throwable $e)
  {
    if ($e instanceof HttpException) {
      return $e->getStatusCode();
    }

    if ($e->getCode() >= 400 && $e->getCode() < 600) {
      return $e->getCode();
    }


    return 500;
  }

  private static function getCodePreview($filePath, $errorLine, $padding = 3)
  {
    /* ... kode lama ... */
    if (!file_exists($filePath) || !is_readable($filePath)) return [];
    $lines = file($filePath);
    $start = max(0, $errorLine - $padding - 1);
    $length = ($errorLine + $padding) - $start;
    $sliced = array_slice($lines, $start, $length, true);
    $preview = [];
    foreach ($sliced as $lineNum => $lineCode) {
      $preview[] = ['number' => $lineNum + 1, 'code' => htmlspecialchars($lineCode), 'highlight' => ($lineNum + 1) === $errorLine];
    }
    return $preview;
  }
}
