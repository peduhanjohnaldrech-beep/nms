<?php
/**
 * backup_cron.php — run via cron to create a daily database backup.
 *
 * Cron example (runs at 2 AM every day):
 *   0 2 * * * php /var/www/nms/database/backup_cron.php >> /var/www/nms/database/backups/backup.log 2>&1
 */
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/config/config.php';
require BASE_PATH . '/app/helpers/BackupScheduler.php';

$filename = BackupScheduler::forceBackup();

$ts = date('Y-m-d H:i:s');
if ($filename) {
    echo "[{$ts}] Backup created: {$filename}\n";
} else {
    echo "[{$ts}] ERROR: Backup failed — check mysqldump is accessible.\n";
    exit(1);
}
