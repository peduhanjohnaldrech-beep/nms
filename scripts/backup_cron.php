<?php
/**
 * NMS Daily Database Backup Script
 *
 * Run via Windows Task Scheduler daily (e.g. 2:00 AM).
 * This script is completely independent of the web server —
 * it runs directly with PHP CLI and always creates a fresh backup.
 *
 * Usage (manual test):
 *   php C:\xampp\htdocs\nms\scripts\backup_cron.php
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/helpers/BackupScheduler.php';

$timestamp = date('Y-m-d H:i:s');
$logFile   = BASE_PATH . '/database/backups/backup.log';

// Ensure backups dir exists
$backupDir = BASE_PATH . '/database/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

// Run the backup
$filename = BackupScheduler::forceBackup();

if ($filename) {
    $path    = $backupDir . '/' . $filename;
    $sizeKb  = round(filesize($path) / 1024, 1);
    $message = "[$timestamp] SUCCESS — $filename ($sizeKb KB)\n";
    echo $message;
    file_put_contents($logFile, $message, FILE_APPEND);
    exit(0);
} else {
    $message = "[$timestamp] FAILED — mysqldump error. Check that C:\\xampp\\mysql\\bin\\mysqldump.exe is accessible.\n";
    echo $message;
    file_put_contents($logFile, $message, FILE_APPEND);
    exit(1);
}
