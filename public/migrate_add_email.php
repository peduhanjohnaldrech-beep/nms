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

    $cols1 = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'")->fetchAll();
    if ($cols1) {
        echo '<p style="color:green">✓ <strong>email</strong> column already exists.</p>';
    } else {
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL AFTER full_name");
        echo '<p style="color:green">✓ <strong>email</strong> column added.</p>';
    }

    $cols2 = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_secondary'")->fetchAll();
    if ($cols2) {
        echo '<p style="color:green">✓ <strong>email_secondary</strong> column already exists.</p>';
    } else {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_secondary VARCHAR(255) NULL AFTER email");
        echo '<p style="color:green">✓ <strong>email_secondary</strong> column added.</p>';
    }

    echo '<p><a href="/nms/public/login">Go to login</a> &mdash; <strong>Delete this file (migrate_add_email.php) now!</strong></p>';
} catch (Exception $e) {
    echo '<p style="color:red">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
