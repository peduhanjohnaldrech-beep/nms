<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\Import;
use App\Models\StoredFile;

class ImportController extends Controller
{
    private Import     $model;
    private StoredFile $fileModel;

    public function __construct()
    {
        $this->model     = new Import();
        $this->fileModel = new StoredFile();
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        $filters = [
            'date_from'   => trim($_GET['date_from']   ?? ''),
            'date_to'     => trim($_GET['date_to']     ?? ''),
            'imported_by' => trim($_GET['imported_by'] ?? ''),
            'folder'      => trim($_GET['folder']      ?? ''),
        ];
        $allowedSorts = ['import_date','imported_by_name','folder','total_rows','success_count','error_count'];
        $sort = in_array($_GET['sort'] ?? '', $allowedSorts) ? $_GET['sort'] : 'import_date';
        $dir  = strtoupper($_GET['dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        $logs    = $this->model->getImportLogs($filters, $sort, $dir);
        $folders = $this->model->getImportFolders();

        $this->view('import/index', compact('logs', 'filters', 'sort', 'dir', 'folders'));
    }

    public function storage(): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        $tab    = in_array($_GET['tab'] ?? '', ['imports', 'files']) ? $_GET['tab'] : 'imports';
        $folder = $this->sanitizeFolderName(trim($_GET['folder'] ?? ''));

        // Beneficiary imports tab data
        $importFolders = $this->model->getImportFolders();
        $importFiles   = $this->model->getStorageContents($folder);

        // Other files tab data
        $fileFolders   = $this->fileModel->getFolders();
        $otherFiles    = $this->fileModel->getFiles($folder);

        $this->view('import/storage', compact(
            'tab', 'folder',
            'importFolders', 'importFiles',
            'fileFolders',   'otherFiles'
        ));
    }

    public function uploadOtherFileFromGdrive(): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/import/storage?tab=files'); }
        $this->validateCsrf();

        $token    = trim($_POST['gdrive_token']    ?? '');
        $fileId   = trim($_POST['gdrive_file_id']  ?? '');
        $filename = basename(trim($_POST['gdrive_filename'] ?? 'file'));
        $folder   = $this->sanitizeFolderName($_POST['folder'] ?? '');

        if (!$token || !$fileId) {
            Session::flash('error', 'Missing Google Drive credentials.');
            $this->redirect('/import/storage?tab=files');
            return;
        }

        $url       = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media";
        $ext       = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $savedName = 'file_' . time() . '_' . bin2hex(random_bytes(4)) . ($ext ? '.' . $ext : '');
        $storageDir = FILES_STORAGE_PATH . ($folder ? '/' . $folder : '');
        if (!is_dir($storageDir)) mkdir($storageDir, 0755, true);

        $destPath = $storageDir . '/' . $savedName;
        $fp       = fopen($destPath, 'wb');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING       => '',
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($httpCode !== 200) {
            if (file_exists($destPath)) unlink($destPath);
            Session::flash('error', "Could not download file from Google Drive (HTTP {$httpCode}).");
            $this->redirect('/import/storage?tab=files');
            return;
        }

        $finfo    = \finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = \finfo_file($finfo, $destPath);
        \finfo_close($finfo);

        $this->fileModel->insert([
            'original_filename' => $filename,
            'saved_filename'    => $savedName,
            'folder'            => $folder ?: null,
            'file_size'         => filesize($destPath),
            'mime_type'         => $mimeType,
            'uploaded_by'       => Session::get('user_id'),
            'uploaded_at'       => date('Y-m-d H:i:s'),
        ]);

        Session::flash('success', "File \"{$filename}\" imported from Google Drive.");
        $this->redirect('/import/storage?tab=files' . ($folder ? '&folder=' . urlencode($folder) : ''));
    }

    public function uploadOtherFile(): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/import/storage?tab=files'); }
        $this->validateCsrf();

        if (empty($_FILES['other_file']['name'])) {
            Session::flash('error', 'Please select a file.');
            $this->redirect('/import/storage?tab=files');
            return;
        }

        $file   = $_FILES['other_file'];
        $folder = $this->sanitizeFolderName($_POST['folder'] ?? '');

        if ($file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Upload failed. Code: ' . $file['error']);
            $this->redirect('/import/storage?tab=files');
            return;
        }

        $origName    = basename($file['name']);
        $ext         = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $savedName   = 'file_' . time() . '_' . bin2hex(random_bytes(4)) . ($ext ? '.' . $ext : '');
        $storageDir  = FILES_STORAGE_PATH . ($folder ? '/' . $folder : '');

        if (!is_dir($storageDir)) mkdir($storageDir, 0755, true);

        $destPath = $storageDir . '/' . $savedName;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            Session::flash('error', 'Failed to save file.');
            $this->redirect('/import/storage?tab=files');
            return;
        }

        $finfo    = \finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = \finfo_file($finfo, $destPath);
        \finfo_close($finfo);

        $this->fileModel->insert([
            'original_filename' => $origName,
            'saved_filename'    => $savedName,
            'folder'            => $folder ?: null,
            'file_size'         => $file['size'],
            'mime_type'         => $mimeType,
            'uploaded_by'       => Session::get('user_id'),
            'uploaded_at'       => date('Y-m-d H:i:s'),
        ]);

        Session::flash('success', "File \"{$origName}\" uploaded.");
        $this->redirect('/import/storage?tab=files' . ($folder ? '&folder=' . urlencode($folder) : ''));
    }

    public function viewOtherFile(int $id): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        $file = $this->fileModel->getById($id);
        if (!$file) { $this->renderPreviewError('File not found.'); return; }

        $folder   = $file['folder'] ?? '';
        $filepath = FILES_STORAGE_PATH . ($folder ? '/' . $folder : '') . '/' . $file['saved_filename'];

        if (!file_exists($filepath)) { $this->renderPreviewError('File no longer exists on the server.'); return; }

        $this->renderPreview($filepath, $file['mime_type'] ?? '', $file['original_filename']);
    }

    public function viewImportFile(int $id): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        $log = $this->model->getImportLogById($id);
        if (!$log || empty($log['saved_filename'])) { $this->renderPreviewError('File not found.'); return; }

        $folder   = $log['folder'] ?? '';
        $filepath = IMPORT_STORAGE_PATH . ($folder ? '/' . $folder : '') . '/' . $log['saved_filename'];

        if (!file_exists($filepath)) { $this->renderPreviewError('File no longer exists on the server.'); return; }

        $ext      = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $mime     = $ext === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'application/vnd.ms-excel';

        $this->renderPreview($filepath, $mime, $log['filename'] ?? $log['saved_filename']);
    }

    private function renderPreview(string $filepath, string $mime, string $displayName): void
    {
        // Images — serve inline
        if (str_starts_with($mime, 'image/')) {
            header('Content-Type: ' . $mime);
            header('Content-Disposition: inline; filename="' . addslashes($displayName) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }

        // PDF — serve inline
        if ($mime === 'application/pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . addslashes($displayName) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }

        // Excel — render as HTML table via PhpSpreadsheet
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        if (in_array($ext, ['xlsx', 'xls'])) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
                $writer      = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
                header('Content-Type: text/html; charset=utf-8');
                echo '<style>
                    body{font-family:sans-serif;font-size:13px;margin:0;padding:8px;}
                    table{border-collapse:collapse;width:100%;}
                    td,th{border:1px solid #dee2e6;padding:4px 8px;white-space:nowrap;}
                    tr:nth-child(even){background:#f8f9fa;}
                    thead tr{background:#e9ecef;font-weight:600;}
                </style>';
                $writer->save('php://output');
            } catch (\Exception $e) {
                $this->renderPreviewError('Could not render spreadsheet: ' . $e->getMessage());
            }
            exit;
        }

        // Plain text / CSV
        if (str_starts_with($mime, 'text/')) {
            $content = htmlspecialchars(file_get_contents($filepath));
            header('Content-Type: text/html; charset=utf-8');
            echo "<style>body{font-family:monospace;font-size:13px;margin:0;padding:12px;white-space:pre-wrap;word-break:break-all;}</style>";
            echo $content;
            exit;
        }

        // Unsupported
        $this->renderPreviewError("Preview is not available for this file type (<code>{$mime}</code>).<br>Please download the file to view it.");
    }

    private function renderPreviewError(string $msg): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo "<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;color:#6c757d;}</style>";
        echo "<div style='text-align:center'><div style='font-size:2rem'>⚠️</div><p>{$msg}</p></div>";
        exit;
    }

    public function downloadOtherFile(int $id): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        $file = $this->fileModel->getById($id);
        if (!$file) {
            Session::flash('error', 'File not found.');
            $this->redirect('/import/storage?tab=files');
            return;
        }

        $folder   = $file['folder'] ?? '';
        $filepath = FILES_STORAGE_PATH . ($folder ? '/' . $folder : '') . '/' . $file['saved_filename'];

        if (!file_exists($filepath)) {
            Session::flash('error', 'File no longer exists on the server.');
            $this->redirect('/import/storage?tab=files');
            return;
        }

        header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . addslashes($file['original_filename']) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private, no-cache');
        readfile($filepath);
        exit;
    }

    public function deleteOtherFile(int $id): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/import/storage?tab=files'); }
        $this->validateCsrf();

        $file = $this->fileModel->getById($id);
        if (!$file) {
            Session::flash('error', 'File not found.');
            $this->redirect('/import/storage?tab=files');
            return;
        }

        $folder   = $file['folder'] ?? '';
        $filepath = FILES_STORAGE_PATH . ($folder ? '/' . $folder : '') . '/' . $file['saved_filename'];
        if (file_exists($filepath)) unlink($filepath);

        $this->fileModel->deleteFile($id);
        Session::flash('success', "File \"{$file['original_filename']}\" deleted.");
        $this->redirect('/import/storage?tab=files' . ($folder ? '&folder=' . urlencode($folder) : ''));
    }

    public function createOtherFolder(): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/import/storage?tab=files'); }
        $this->validateCsrf();

        $name = $this->sanitizeFolderName($_POST['folder_name'] ?? '');
        if (empty($name)) {
            Session::flash('error', 'Invalid folder name.');
            $this->redirect('/import/storage?tab=files');
            return;
        }

        $path = FILES_STORAGE_PATH . '/' . $name;
        if (is_dir($path)) {
            Session::flash('error', "Folder \"{$name}\" already exists.");
        } elseif (!mkdir($path, 0755, true)) {
            Session::flash('error', 'Failed to create folder.');
        } else {
            Session::flash('success', "Folder \"{$name}\" created.");
        }
        $this->redirect('/import/storage?tab=files');
    }

    public function deleteOtherFolder(): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/import/storage?tab=files'); }
        $this->validateCsrf();

        $name = $this->sanitizeFolderName($_POST['folder_name'] ?? '');
        if (empty($name)) { $this->redirect('/import/storage?tab=files'); return; }

        $path  = FILES_STORAGE_PATH . '/' . $name;
        $files = is_dir($path) ? array_diff(scandir($path), ['.', '..']) : [];
        if (!empty($files)) {
            Session::flash('error', "Folder \"{$name}\" is not empty.");
        } else {
            if (is_dir($path)) rmdir($path);
            Session::flash('success', "Folder \"{$name}\" deleted.");
        }
        $this->redirect('/import/storage?tab=files');
    }

    public function uploadFromGdrive(): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/import'); }
        $this->validateCsrf();

        $token    = trim($_POST['gdrive_token']    ?? '');
        $fileId   = trim($_POST['gdrive_file_id']  ?? '');
        $filename = basename(trim($_POST['gdrive_filename'] ?? 'import.xlsx'));
        $isSheet  = ($_POST['gdrive_is_sheet'] ?? '0') === '1';

        if (!$token || !$fileId) {
            Session::flash('error', 'Missing Google Drive credentials.');
            $this->redirect('/import');
            return;
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($isSheet || !in_array($ext, ['xlsx', 'xls'])) {
            $filename = preg_replace('/\.(xlsx|xls)$/i', '', $filename) . '.xlsx';
            $ext = 'xlsx';
        }

        $url = $isSheet
            ? "https://www.googleapis.com/drive/v3/files/{$fileId}/export?mimeType=application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            : "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media";

        $savedName = 'import_' . time() . '_' . session_id() . '.' . $ext;
        $filepath  = UPLOAD_PATH . '/' . $savedName;
        $fp        = fopen($filepath, 'wb');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING       => '',
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($httpCode !== 200) {
            if (file_exists($filepath)) unlink($filepath);
            Session::flash('error', "Could not download file from Google Drive (HTTP {$httpCode}).");
            $this->redirect('/import');
            return;
        }

        $result = $this->model->processExcel($filepath);

        if (!empty($result['errors'])) {
            unlink($filepath);
            Session::flash('error', implode('<br>', $result['errors']));
            $this->redirect('/import');
            return;
        }

        Session::set('import_rows',          $result['rows']);
        Session::set('import_file',          $filepath);
        Session::set('import_filename',      $filename);
        Session::set('import_temp_filename', $savedName);
        Session::set('import_source',        'Google');

        $this->view('import/preview', [
            'rows'     => $result['rows'],
            'filename' => $filename,
            'folders'  => $this->model->getImportFolders(),
        ]);
    }

    public function upload(): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/import'); }
        $this->validateCsrf();

        if (empty($_FILES['excel_file']['name'])) {
            Session::flash('error', 'Please select a file to upload.');
            $this->redirect('/import');
            return;
        }

        $file = $_FILES['excel_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            Session::flash('error', 'Only .xlsx and .xls files are accepted.');
            $this->redirect('/import');
            return;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'File upload failed. Code: ' . $file['error']);
            $this->redirect('/import');
            return;
        }


        $filename = 'import_' . time() . '_' . session_id() . '.' . $ext;
        $filepath = UPLOAD_PATH . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            Session::flash('error', 'Failed to save uploaded file.');
            $this->redirect('/import');
            return;
        }

        $result = $this->model->processExcel($filepath);

        if (!empty($result['errors'])) {
            unlink($filepath);
            Session::flash('error', implode('<br>', $result['errors']));
            $this->redirect('/import');
            return;
        }

        Session::set('import_rows',          $result['rows']);
        Session::set('import_file',          $filepath);
        Session::set('import_filename',      $file['name']);
        Session::set('import_temp_filename', $filename);
        $source = ($_POST['import_source'] ?? '') === 'Google' ? 'Google' : 'Excel';
        Session::set('import_source',        $source);

        $this->view('import/preview', [
            'rows'     => $result['rows'],
            'filename' => $file['name'],
            'folders'  => $this->model->getImportFolders(),
        ]);
    }

    public function confirm(): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/import'); }
        $this->validateCsrf();

        $rows         = Session::get('import_rows', []);
        $tempFile     = Session::get('import_file', '');
        $origFilename = Session::get('import_filename', '');
        $tempFilename = Session::get('import_temp_filename', '');
        $importSource = Session::get('import_source', 'Excel');

        if (empty($rows)) {
            Session::flash('error', 'No import data found. Please upload the file again.');
            $this->redirect('/import');
            return;
        }

        // Apply duplicate handling choice
        $duplicateAction = ($_POST['duplicate_action'] ?? 'update') === 'skip' ? 'skip' : 'update';
        $skipRowsRaw     = trim($_POST['skip_rows'] ?? '');
        $skipRowNums     = $skipRowsRaw !== ''
            ? array_map('intval', explode(',', $skipRowsRaw))
            : [];

        $rows = array_filter($rows, function (array $row) use ($duplicateAction, $skipRowNums): bool {
            if (in_array((int)$row['rowNum'], $skipRowNums, true)) return false;
            if ($row['status'] === 'update' && $duplicateAction === 'skip') return false;
            return true;
        });
        $rows = array_values($rows);

        // Validate and resolve selected folder
        $folder = $this->sanitizeFolderName($_POST['folder'] ?? '');

        // Move temp file to permanent storage (into folder subdirectory if selected)
        $savedFilename = null;
        if (!empty($tempFile) && file_exists($tempFile)) {
            $storageDir = IMPORT_STORAGE_PATH . ($folder ? '/' . $folder : '');
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }
            $destPath = $storageDir . '/' . $tempFilename;
            if (rename($tempFile, $destPath)) {
                $savedFilename = $tempFilename;
            } else {
                if (copy($tempFile, $destPath)) {
                    unlink($tempFile);
                    $savedFilename = $tempFilename;
                } else {
                    unlink($tempFile);
                }
            }
        }

        Session::remove('import_rows');
        Session::remove('import_file');
        Session::remove('import_filename');
        Session::remove('import_temp_filename');
        Session::remove('import_source');

        try {
            $result = $this->model->executeImport(
                $rows, Session::get('user_id'), $origFilename, $savedFilename, $folder ?: null, $importSource
            );

            Session::flash('success', sprintf(
                'Import complete: %d records imported, %d errors.',
                $result['success'], count($result['errors'])
            ));
            if (!empty($result['errors'])) {
                Session::flash('warning', 'Errors: ' . implode('; ', array_slice($result['errors'], 0, 5)));
            }
        } catch (\Exception $e) {
            // Import failed — remove the saved file so it doesn't become an orphan
            if ($savedFilename) {
                $orphan = IMPORT_STORAGE_PATH . ($folder ? '/' . $folder : '') . '/' . $savedFilename;
                if (file_exists($orphan)) unlink($orphan);
            }
            Session::flash('error', 'Import failed: ' . $e->getMessage());
        }

        $this->redirect('/import');
    }

    public function delete(int $id): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/import'); }
        $this->validateCsrf();

        $log = $this->model->getImportLogById($id);
        if (!$log) {
            Session::flash('error', 'Import record not found.');
            $this->redirect('/import');
            return;
        }

        if (!empty($log['saved_filename'])) {
            $folder   = $log['folder'] ?? '';
            $filepath = IMPORT_STORAGE_PATH . ($folder ? '/' . $folder : '') . '/' . $log['saved_filename'];
            if (file_exists($filepath)) unlink($filepath);
        }

        $this->model->deleteLog($id);
        Session::flash('success', 'Import record deleted.');
        $this->redirect('/import');
    }

    public function download(int $id): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        $log = $this->model->getImportLogById($id);
        if (!$log || empty($log['saved_filename'])) {
            Session::flash('error', 'File not found.');
            $this->redirect('/import');
            return;
        }

        $folder   = $log['folder'] ?? '';
        $filepath = IMPORT_STORAGE_PATH . ($folder ? '/' . $folder : '') . '/' . $log['saved_filename'];

        if (!file_exists($filepath)) {
            Session::flash('error', 'The file no longer exists on the server.');
            $this->redirect('/import');
            return;
        }

        $displayName = $log['filename'] ?: $log['saved_filename'];
        $ext         = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $mime        = $ext === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'application/vnd.ms-excel';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($displayName) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private, no-cache');
        readfile($filepath);
        exit;
    }

    public function downloadTemplate(): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            Session::flash('error', 'PhpSpreadsheet is not available.');
            $this->redirect('/import');
        }

        $headers = [
            'Last Name', 'First Name', 'Middle Name', 'Suffix',
            'Date of Birth', 'Sex', 'Barangay', 'Purok/Zone',
            'Household No.', 'InCode', "Mother's Name", "Father's Name",
            'Contact Number', 'Income Classification', 'Monthly Household Income',
            '4Ps Member', 'NHTS-PR Status', 'PhilHealth Status',
            'Assessment Date', 'Weight (kg)', 'Height (cm)', 'MUAC (cm)',
        ];

        $hints = [
            'e.g. Dela Cruz', 'e.g. Juan', 'e.g. Santos', 'e.g. Jr.',
            'YYYY-MM-DD', 'Male or Female', 'e.g. Poblacion', 'e.g. Purok 1',
            'e.g. 001', 'e.g. 001', 'e.g. Maria Dela Cruz', 'e.g. Jose Dela Cruz',
            'e.g. 09171234567', 'Poor / Near Poor / Non-Poor', 'e.g. 5000',
            'Yes or No', 'Poor or Not Poor', 'Member / Indigent / Non-member',
            'YYYY-MM-DD', 'e.g. 12.5', 'e.g. 85.0', 'e.g. 13.5',
        ];

        $sample = [
            'Dela Cruz', 'Juan', 'Santos', 'Jr.',
            date('Y-m-d', strtotime('-2 years')), 'Male', 'Poblacion', 'Purok 1',
            '001', 'BAR001', 'Maria Santos Dela Cruz', 'Jose Dela Cruz',
            '09171234567', 'Poor', '4500',
            'Yes', 'Poor', 'Indigent',
            date('Y-m-d', strtotime('-1 month')), '12.5', '85.0', '13.5',
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        /* ── Sheet 1: Template ── */
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        $colCount = count($headers);
        $colWidths = [16,14,14,8,14,8,14,12,12,10,20,20,14,18,14,8,12,16,14,10,10,10];

        for ($i = 0; $i < $colCount; $i++) {
            $col  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);

            // Row 1 — headers
            $sheet->setCellValue("{$col}1", $headers[$i]);
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                           'startColor' => ['argb' => 'FF1D6F42']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                               'color' => ['argb' => 'FF145228']]],
            ]);

            // Row 2 — hints
            $sheet->setCellValue("{$col}2", $hints[$i]);
            $sheet->getStyle("{$col}2")->applyFromArray([
                'font' => ['italic' => true, 'color' => ['argb' => 'FF555555'], 'size' => 9],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                           'startColor' => ['argb' => 'FFEAF5EC']],
                'alignment' => ['wrapText' => true, 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                               'color' => ['argb' => 'FF000000']]],
            ]);

            // Row 3 — sample data
            $sheet->setCellValue("{$col}3", $sample[$i]);
            $sheet->getStyle("{$col}3")->applyFromArray([
                'font' => ['color' => ['argb' => 'FF7C4D00'], 'size' => 10],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                           'startColor' => ['argb' => 'FFFFF8E1']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                               'color' => ['argb' => 'FF000000']]],
            ]);

            // Column width
            $sheet->getColumnDimension($col)->setWidth($colWidths[$i] ?? 14);
        }

        // Row heights
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Freeze first 3 rows (header + hints + sample)
        $sheet->freezePane('A4');

        // Data validation dropdowns
        $dropdowns = [
            'F'  => '"Male,Female"',
            'N'  => '"Poor,Near Poor,Non-Poor"',
            'P'  => '"Yes,No"',
            'Q'  => '"Poor,Not Poor"',
            'R'  => '"Member,Indigent,Non-member"',
        ];
        foreach ($dropdowns as $col => $formula) {
            $validation = $sheet->getCell("{$col}4")->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(false);
            $validation->setFormula1($formula);
            $validation->setSqref("{$col}4:{$col}1048576");
        }

        // Border on data area rows 4+ (visual guide for first 50 rows)
        $sheet->getStyle('A4:V53')->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                           'color' => ['argb' => 'FF000000']]],
        ]);

        /* ── Sheet 2: Instructions ── */
        $instrSheet = $spreadsheet->createSheet();
        $instrSheet->setTitle('Instructions');
        $instrSheet->getColumnDimension('A')->setWidth(90);

        $lines = [
            ['BENEFICIARY IMPORT TEMPLATE — INSTRUCTIONS', 'header'],
            ['', null],
            ['1. Fill in data starting from Row 4. Rows 1–3 are for reference only.', 'body'],
            ['2. Do NOT change column order or remove/add columns.', 'body'],
            ['3. Date fields must use YYYY-MM-DD format (e.g., 2024-06-15).', 'body'],
            ['4. Sex: enter "Male" or "Female" exactly.', 'body'],
            ['5. Income Classification: "Poor", "Near Poor", or "Non-Poor".', 'body'],
            ['6. 4Ps Member: "Yes" or "No".', 'body'],
            ['7. NHTS-PR Status: "Poor" or "Not Poor".', 'body'],
            ['8. PhilHealth Status: "Member", "Indigent", or "Non-member".', 'body'],
            ['9. Weight in kilograms (e.g., 12.5), Height in centimeters (e.g., 85.0).', 'body'],
            ['10. MUAC (Mid-Upper Arm Circumference) in centimeters.', 'body'],
            ['11. Middle Name, Suffix, Height (cm), MUAC (cm) are optional.', 'body'],
            ['12. Save as .xlsx before uploading.', 'body'],
            ['', null],
            ['Column Reference:', 'subheader'],
        ];
        foreach ($headers as $idx => $hdr) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $lines[] = ["  Column {$col}: {$hdr} — {$hints[$idx]}", 'col'];
        }

        foreach ($lines as $rowIdx => [$text, $style]) {
            $instrSheet->setCellValue("A" . ($rowIdx + 1), $text);
            if ($style === 'header') {
                $instrSheet->getStyle("A" . ($rowIdx + 1))->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF1D6F42']],
                ]);
            } elseif ($style === 'subheader') {
                $instrSheet->getStyle("A" . ($rowIdx + 1))->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                ]);
            } elseif ($style === 'col') {
                $instrSheet->getStyle("A" . ($rowIdx + 1))->applyFromArray([
                    'font' => ['color' => ['argb' => 'FF444444'], 'size' => 10],
                ]);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'beneficiary_import_template_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function createFolder(): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/import'); }
        $this->validateCsrf();

        $name = $this->sanitizeFolderName($_POST['folder_name'] ?? '');

        if (empty($name)) {
            Session::flash('error', 'Folder name is required and may only contain letters, numbers, spaces, hyphens, and underscores.');
            $this->redirect('/import');
            return;
        }

        $path = IMPORT_STORAGE_PATH . '/' . $name;

        if (is_dir($path)) {
            Session::flash('error', "Folder \"{$name}\" already exists.");
            $this->redirect('/import');
            return;
        }

        if (!mkdir($path, 0755, true)) {
            Session::flash('error', 'Failed to create folder.');
            $this->redirect('/import');
            return;
        }

        Session::flash('success', "Folder \"{$name}\" created.");
        $this->redirect('/import');
    }

    public function deleteFolder(): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/import'); }
        $this->validateCsrf();

        $name = $this->sanitizeFolderName($_POST['folder_name'] ?? '');
        if (empty($name)) { $this->redirect('/import'); return; }

        $path = IMPORT_STORAGE_PATH . '/' . $name;

        if (!is_dir($path)) {
            Session::flash('error', 'Folder not found.');
            $this->redirect('/import');
            return;
        }

        // Only allow deleting empty folders
        $files = array_diff(scandir($path), ['.', '..']);
        if (!empty($files)) {
            Session::flash('error', "Folder \"{$name}\" is not empty. Move or delete its files first.");
            $this->redirect('/import');
            return;
        }

        rmdir($path);
        Session::flash('success', "Folder \"{$name}\" deleted.");
        $this->redirect('/import');
    }

    private function sanitizeFolderName(string $name): string
    {
        $name = trim($name);
        // Allow letters, numbers, spaces, hyphens, underscores only
        if (!preg_match('/^[\w\s\-]+$/', $name)) return '';
        return $name;
    }
}
