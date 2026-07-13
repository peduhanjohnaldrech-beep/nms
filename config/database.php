<?php
return [
    'host'    => $_ENV['DB_HOST']    ?? 'localhost',
    'dbname'  => $_ENV['DB_NAME']    ?? 'nms',
    'user'    => $_ENV['DB_USER']    ?? 'root',
    'pass'    => $_ENV['DB_PASS']    ?? '',
    'charset' => 'utf8mb4',
];
