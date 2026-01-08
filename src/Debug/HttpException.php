<?php

namespace Jtech\Debug;

class HttpException extends \Exception
{
    protected int $statusCode;

    public function __construct(int $statusCode, string $message = '', ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;

        // Pesan default kalau kosong
        if (empty($message)) {
            $message = match ($statusCode) {
                404 => 'Halaman tidak ditemukan.',
                403 => 'Akses ditolak.',
                401 => 'Kamu harus login dulu.',
                500 => 'Server sedang bermasalah.',
                default => 'Terjadi kesalahan HTTP.'
            };
        }

        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
