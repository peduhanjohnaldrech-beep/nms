<?php
/**
 * Run all pending migrations.
 * Usage: php C:\xampp\htdocs\nms\database\run_migrations.php
 */

$migrations = [
    'add_programs_table.sql',
    'add_dispensing_records.sql',
];

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=nms_db;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    foreach ($migrations as $file) {
        $path = __DIR__ . '/' . $file;
        if (!file_exists($path)) {
            echo "SKIP  $file (not found)\n";
            continue;
        }
        $sql = file_get_contents($path);
        $pdo->exec($sql);
        echo "OK    $file\n";
    }

    echo "\nAll migrations done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
