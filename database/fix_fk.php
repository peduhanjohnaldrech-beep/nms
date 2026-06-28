<?php
define('BASE_PATH', dirname(__DIR__));
$pdo = new PDO('sqlite:' . BASE_PATH . '/database/nms.sqlite', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$pdo->exec('PRAGMA foreign_keys = OFF');

$tables = ['assessments','program_enrollments','vitamin_a_records','mnp_records','lns_sq_records','dispensing_records'];

foreach ($tables as $t) {
    $sql = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$t'")->fetchColumn();
    $newSql = str_replace('beneficiaries_old', 'beneficiaries', $sql);
    $tmp = $t . '_tmp';
    $pdo->exec("ALTER TABLE $t RENAME TO $tmp");
    $pdo->exec($newSql);
    $pdo->exec("INSERT INTO $t SELECT * FROM $tmp");
    $pdo->exec("DROP TABLE $tmp");
    echo "Fixed: $t" . PHP_EOL;
}

$pdo->exec('PRAGMA foreign_keys = ON');
echo 'All done.' . PHP_EOL;
