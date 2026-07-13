<?php
namespace App\Controllers;

use Core\ApiController;

class BackupApiController extends ApiController
{
    public function list(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['admin']);

        $backups = \BackupScheduler::listBackups();
        $this->success(['backups' => $backups]);
    }

    public function download(string $filename): void
    {
        $this->requireApiAuth();
        $this->requireRole(['admin']);

        $path = \BackupScheduler::getPath($filename);
        if (!$path) $this->error('Backup file not found', 404);

        \ActivityLog::log('backup', "Admin downloaded backup: $filename via mobile app.");

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        readfile($path);
        exit;
    }
}
