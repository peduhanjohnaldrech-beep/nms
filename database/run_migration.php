<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=nms_db;charset=utf8mb4', 'root', '');
    $sql = file_get_contents(__DIR__ . '/add_dispensing_records.sql');
    $pdo->exec($sql);
    echo "Table created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
