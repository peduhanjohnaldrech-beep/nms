<?php
/**
 * NMS Daily Database Backup Script
 *
 * Runs via cron (Linux/production) or Windows Task Scheduler (local dev).
 * Always creates a fresh backup regardless of the 24-hour throttle used
 * by BackupScheduler::maybeBackup() in the web context.
 *
 * -- Production (DigitalOcean / Linux) ------------------------------------
 * Register in crontab:  crontab -e
 *   0 2 * * * /usr/bin/php /var/www/nms/scripts/backup_cron.php >> /var/www/nms/database/backups/backup.log 2>&1
 *
 * That runs every day at 02:00 server time.  Adjust the path to match your
 * deployment (check with: which php).
 *
 * -- Windows (XAMPP dev) --------------------------------------------------
 * Add to Task Scheduler:
 *   Program:   C:\xampp\php\php.exe
 *   Arguments: C:\xampp\htdocs\nms\scripts\backup_cron.php
 *   Trigger:   Daily at 02:00
 *
 * -- Manual test ----------------------------------------------------------
 *   php /var/www/nms/scripts/backup_cron.php
 *   php C:\xampp\htdocs\nms\scripts\backup_cron.php
 */

define('BASE_PATH', dirname(__DIR__));

// Minimal bootstrap -- only what BackupScheduler needs
require BASE_PATH . '/config/config.php';
require BASE_PATH . '/app/helpers/BackupScheduler.php';

$timestamp = date('Y-m-d H:i:s');
$backupDir = BASE_PATH . '/database/backups';
$logFile   = $backupDir . '/backup.log';

if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$filename = BackupScheduler::forceBackup();

if ($filename) {
    $path   = $backupDir . '/' . $filename;
    $sizeKb = round(filesize($path) / 1024, 1);
    $msg    = "[$timestamp] OK  $filename ({$sizeKb} KB)\n";
    echo $msg;
    file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
    exit(0);
} else {
    $msg = "[$timestamp] ERR mysqldump failed -- verify mysqldump is accessible and DB credentials are correct.\n";
    echo $msg;
    file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
    exit(1);
}
