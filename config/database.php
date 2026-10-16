<?php
return [
    'host'    => getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost'),
    'dbname'  => getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'nms'),
    'user'    => getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root'),
    'pass'    => getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? ''),
    'charset' => 'utf8mb4',
];
