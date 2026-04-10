<?php
/**
 * Public bridge for environments where the web server root is this folder.
 * It forwards requests to apps/laravel-web/public and serves built assets.
 */

$laravelPublic = realpath(__DIR__.'/../apps/laravel-web/public');

if ($laravelPublic === false) {
    http_response_code(500);
    echo 'Laravel public directory was not found.';
    exit;
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$targetPath = realpath($laravelPublic.$requestPath);

if ($targetPath !== false && str_starts_with($targetPath, $laravelPublic) && is_file($targetPath)) {
    $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

    if ($ext !== 'php') {
        $mimeTypes = [
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'mjs' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'map' => 'application/json; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
        ];

        $contentType = $mimeTypes[$ext] ?? (mime_content_type($targetPath) ?: 'application/octet-stream');
        header('Content-Type: '.$contentType);
        readfile($targetPath);
        exit;
    }
}

require $laravelPublic.'/index.php';
