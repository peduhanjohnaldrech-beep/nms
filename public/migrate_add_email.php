<?php
// ONE-TIME migration — adds email column to users table.
// DELETE THIS FILE after running.
try {
    $pdo = new PDO(
        'mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'nms'),
        getenv('DB_USER') ?: 'root',
        getenv('DB_PASS') ?: ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if column already exists
    $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'")->fetchAll();
    if ($cols) {
        echo '<p style="color:green">✓ <strong>email</strong> column already exists. Nothing to do.</p>';
    } else {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL AFTER full_name");
        echo '<p style="color:green">✓ <strong>email</strong> column added successfully.</p>';
    }
    echo '<p><a href="/nms/public/login">Go to login</a> &mdash; <strong>Delete this file (migrate_add_email.php) now!</strong></p>';
} catch (Exception $e) {
    echo '<p style="color:red">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
