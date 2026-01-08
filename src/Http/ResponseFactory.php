<?php

namespace Jtech\Http;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResponseFactory
{
    /**
     * Return Raw Response (String/HTML)
     */
    public function make($content = '', $status = 200, array $headers = [])
    {
        return new Response($content, $status, $headers);
    }

    /**
     * Return JSON Response
     */
    public function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    /**
     * Return View Response
     * Ini otomatis ngerender view lo dan bungkus jadi HTTP Response
     */
    public function view($view, array $data = [], $status = 200, array $headers = [])
    {
        // Panggil View Engine lo yang kemarin
        $content = app('view')->render($view, $data);

        // Set content type html
        $headers = array_merge(['Content-Type' => 'text/html'], $headers);

        return new Response($content, $status, $headers);
    }

    /**
     * Return Redirect Response
     */
    public function redirectTo($path, $status = 302, $headers = [])
    {
        // Kalau path gak pake http, anggap base_url
        if (!preg_match('#^https?://#', $path)) {
            $path = base_url($path);
        }

        return new RedirectResponse($path, $status, $headers);
    }

    /**
     * Return File Download
     */
    public function download($file, $name = null, array $headers = [])
    {
        return new BinaryFileResponse($file, 200, $headers, true, 'attachment');
    }

    /**
     * Return No Content (204)
     */
    public function noContent($status = 204, array $headers = [])
    {
        return new Response('', $status, $headers);
    }
}
