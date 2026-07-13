@echo off
:: NMS Daily Backup — run by Windows Task Scheduler
:: Logs output to database/backups/backup.log

"C:\xampp\php\php.exe" "C:\xampp\htdocs\nms\scripts\backup_cron.php" >> "C:\xampp\htdocs\nms\database\backups\backup.log" 2>&1
