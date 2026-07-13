<?php
// Load .env file
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

date_default_timezone_set('Asia/Manila');

define('APP_NAME',     $_ENV['APP_NAME']     ?? 'NMS');
define('APP_URL',      $_ENV['APP_URL']      ?? 'http://localhost/nms/public');
define('APP_ENV',      $_ENV['APP_ENV']      ?? 'development');
define('APP_REGION',   $_ENV['APP_REGION']   ?? '');
define('APP_PROVINCE', $_ENV['APP_PROVINCE'] ?? '');
define('APP_CITY',     $_ENV['APP_CITY']     ?? '');

// Google Drive file picker keys (optional — leave blank to disable)
define('GOOGLE_API_KEY',   $_ENV['GOOGLE_API_KEY']   ?? '');
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('VIEW_PATH',          BASE_PATH . '/app/views');
define('UPLOAD_PATH',        BASE_PATH . '/public/uploads');
define('IMPORT_STORAGE_PATH', BASE_PATH . '/storage/imports');
define('FILES_STORAGE_PATH',  BASE_PATH . '/storage/files');
