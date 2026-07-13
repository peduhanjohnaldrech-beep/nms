<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;

class BackupController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $backups = \BackupScheduler::listBackups();

        $logFile  = BASE_PATH . '/database/backups/backup.log';
        $logLines = [];
        if (file_exists($logFile)) {
            $lines    = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $logLines = array_reverse(array_slice($lines, -50)); // last 50, newest first
        }

        $this->view('backup/index', ['backups' => $backups, 'logLines' => $logLines]);
    }

    /** Dump the live database and stream it immediately. */
    public function download(): void
    {
        $this->requireAdmin();

        $tmpFile = tempnam(sys_get_temp_dir(), 'nms_live_') . '.sql';

        // Reuse BackupScheduler's dump logic via forceBackup into a temp location
        $cfg    = require BASE_PATH . '/config/database.php';
        $host   = $cfg['host']   ?? 'localhost';
        $dbname = $cfg['dbname'] ?? 'nms';
        $user   = $cfg['user']   ?? 'root';
        $pass   = $cfg['pass']   ?? '';

        $bin     = 'mysqldump';
        $xamppBin = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        if (file_exists($xamppBin)) $bin = $xamppBin;

        $args = [
            escapeshellarg($bin),
            '--host='   . escapeshellarg($host),
            '--user='   . escapeshellarg($user),
            '--single-transaction',
            '--routines',
            '--triggers',
            '--no-tablespaces',
        ];
        if ($pass !== '') $args[] = '--password=' . escapeshellarg($pass);
        $args[] = escapeshellarg($dbname);

        $cmd = implode(' ', $args) . ' > ' . escapeshellarg($tmpFile) . ' 2>&1';
        exec($cmd, $output, $code);

        if ($code !== 0 || !file_exists($tmpFile) || filesize($tmpFile) < 200) {
            @unlink($tmpFile);
            Session::flash('error', 'mysqldump failed. Check that mysqldump is accessible.');
            $this->redirect('/backup');
        }

        $filename = 'nms_live_' . date('Y-m-d_His') . '.sql';
        \ActivityLog::log('backup', 'Admin downloaded a live database dump.');

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmpFile));
        header('Cache-Control: no-cache');
        readfile($tmpFile);
        @unlink($tmpFile);
        exit;
    }

    /** Download a specific auto-backup file by filename. */
    public function downloadFile(string $filename): void
    {
        $this->requireAdmin();

        $path = \BackupScheduler::getPath($filename);

        if (!$path) {
            Session::flash('error', 'Backup file not found.');
            $this->redirect('/backup');
        }

        \ActivityLog::log('backup', "Admin downloaded backup: $filename");

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache');
        readfile($path);
        exit;
    }

    /** Manually trigger an immediate backup. */
    public function create(): void
    {
        $this->requireAdmin();

        $filename = \BackupScheduler::forceBackup();

        if ($filename) {
            \ActivityLog::log('backup', "Admin manually created backup: $filename");
            Session::flash('success', "Backup created: <strong>$filename</strong>");
        } else {
            Session::flash('error', 'Backup failed. Ensure mysqldump is accessible from PHP.');
        }

        $this->redirect('/backup');
    }
}
