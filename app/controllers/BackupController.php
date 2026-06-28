<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;

class BackupController extends Controller
{
    public function download(): void
    {
        $this->requireAdmin();

        $dbPath = BASE_PATH . '/database/nms.sqlite';

        if (!file_exists($dbPath)) {
            Session::flash('error', 'Database file not found.');
            $this->redirect('/dashboard');
        }

        $filename = 'nms_backup_' . date('Y-m-d_His') . '.sqlite';

        \ActivityLog::log('backup', 'Admin downloaded a database backup.');

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($dbPath));
        header('Cache-Control: no-cache');
        readfile($dbPath);
        exit;
    }
}
