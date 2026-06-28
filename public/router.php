<?php
// Router script for PHP built-in server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files directly (css, js, images, uploads)
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Everything else goes through index.php
require __DIR__ . '/index.php';
