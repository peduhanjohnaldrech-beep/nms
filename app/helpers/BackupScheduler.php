<?php

/**
 * BackupScheduler
 *
 * Creates a mysqldump of the NMS database automatically.
 * Called from SyncController::push() so a backup is created
 * at most once per day without any cron job or Task Scheduler.
 *
 * Backups stored in:  database/backups/nms_backup_YYYY-MM-DD_HHiiss.sql
 * Maximum kept:       7 (oldest auto-pruned)
 */
class BackupScheduler
{
    private const INTERVAL_HOURS = 24;
    private const MAX_BACKUPS    = 7;

    private static function backupDir(): string
    {
        return BASE_PATH . '/database/backups';
    }

    private static function markerFile(): string
    {
        return self::backupDir() . '/.last_backup';
    }

    /**
     * Call this on every sync push.
     * Does nothing if a backup was already created within INTERVAL_HOURS.
     */
    public static function maybeBackup(): void
    {
        $dir = self::backupDir();
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $marker     = self::markerFile();
        $lastBackup = file_exists($marker) ? (int)file_get_contents($marker) : 0;

        if (time() - $lastBackup < self::INTERVAL_HOURS * 3600) return;

        $filename = 'nms_backup_' . date('Y-m-d_His') . '.sql';
        $dest     = $dir . '/' . $filename;

        if (self::runDump($dest)) {
            file_put_contents($marker, (string)time());
            self::pruneOldBackups();
        }
    }

    /**
     * Force an immediate backup regardless of the 24-hour interval.
     * Returns the filename on success, null on failure.
     */
    public static function forceBackup(): ?string
    {
        $dir = self::backupDir();
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $filename = 'nms_backup_' . date('Y-m-d_His') . '.sql';
        $dest     = $dir . '/' . $filename;

        if (self::runDump($dest)) {
            file_put_contents(self::markerFile(), (string)time());
            self::pruneOldBackups();
            return $filename;
        }

        return null;
    }

    /**
     * Returns metadata for all existing backups, newest first.
     */
    public static function listBackups(): array
    {
        $dir = self::backupDir();
        if (!is_dir($dir)) return [];

        $files = glob($dir . '/nms_backup_*.sql') ?: [];
        rsort($files);

        return array_map(static function (string $path): array {
            return [
                'filename'   => basename($path),
                'size_bytes' => (int)filesize($path),
                'created_at' => (int)filemtime($path),
            ];
        }, $files);
    }

    /**
     * Returns the full path for a given filename after validation.
     */
    public static function getPath(string $filename): ?string
    {
        if (!preg_match('/^nms_backup_[\d_-]+\.sql$/', $filename)) return null;
        $path = self::backupDir() . '/' . $filename;
        return file_exists($path) ? $path : null;
    }

    // ── Private ────────────────────────────────────────────

    private static function runDump(string $dest): bool
    {
        $cfg    = require BASE_PATH . '/config/database.php';
        $host   = $cfg['host']   ?? 'localhost';
        $dbname = $cfg['dbname'] ?? 'nms';
        $user   = $cfg['user']   ?? 'root';
        $pass   = $cfg['pass']   ?? '';

        // Locate mysqldump — XAMPP Windows path first, then system PATH
        $bin = 'mysqldump';
        $xamppBin = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        if (file_exists($xamppBin)) $bin = $xamppBin;

        // Build argument list
        $args = [
            escapeshellarg($bin),
            '--host='   . escapeshellarg($host),
            '--user='   . escapeshellarg($user),
            '--single-transaction',
            '--routines',
            '--triggers',
            '--no-tablespaces',
        ];

        // Add password only if set (avoids mysqldump warning when empty)
        if ($pass !== '') {
            $args[] = '--password=' . escapeshellarg($pass);
        }

        $args[] = escapeshellarg($dbname);

        $cmd = implode(' ', $args) . ' > ' . escapeshellarg($dest) . ' 2>&1';
        exec($cmd, $output, $code);

        // A valid dump should be at least a few hundred bytes
        return $code === 0 && file_exists($dest) && filesize($dest) > 200;
    }

    private static function pruneOldBackups(): void
    {
        $files = glob(self::backupDir() . '/nms_backup_*.sql') ?: [];
        rsort($files);
        foreach (array_slice($files, self::MAX_BACKUPS) as $old) {
            @unlink($old);
        }
    }
}
