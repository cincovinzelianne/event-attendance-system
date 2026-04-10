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
        $contentType = mime_content_type($targetPath) ?: 'application/octet-stream';
        header('Content-Type: '.$contentType);
        readfile($targetPath);
        exit;
    }
}

require $laravelPublic.'/index.php';
