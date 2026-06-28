<?php
/**
 * Initialize the SQLite database from nms_sqlite.sql
 * Run once: php database/init_sqlite.php
 */

define('BASE_PATH', dirname(__DIR__));

$dbPath = BASE_PATH . '/database/nms.sqlite';
$schema = BASE_PATH . '/database/nms_sqlite.sql';

if (!file_exists($schema)) {
    die("Schema file not found: $schema\n");
}

$pdo = new PDO("sqlite:$dbPath", null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$sql = file_get_contents($schema);

// Strip single-line comments, then split on semicolons
$sql = preg_replace('/^--.*$/m', '', $sql);
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    fn($s) => $s !== ''
);

foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
    } catch (PDOException $e) {
        echo "Warning: " . $e->getMessage() . "\n  → $stmt\n\n";
    }
}

echo "SQLite database initialized at: $dbPath\n";
echo "Default login: admin / Admin@1234\n";
