<?php
/**
 * Migration: Add photo column to beneficiaries table
 * Run: php database/add_photo_column.php
 */

define('BASE_PATH', dirname(__DIR__));

$dbPath = BASE_PATH . '/database/nms.sqlite';

if (!file_exists($dbPath)) {
    die("Database not found: $dbPath\n");
}

$pdo = new PDO("sqlite:$dbPath", null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

try {
    $pdo->exec("ALTER TABLE beneficiaries ADD COLUMN photo TEXT");
    echo "Migration successful: 'photo' column added to beneficiaries table.\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'duplicate column')) {
        echo "Column 'photo' already exists — nothing to do.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
