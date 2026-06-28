<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=nms_db;charset=utf8mb4', 'root', '');
    $sql = file_get_contents(__DIR__ . '/add_programs_table.sql');
    $pdo->exec($sql);
    echo "Programs table created and seeded successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
