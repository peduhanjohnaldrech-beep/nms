<?php

namespace App\Models;

use Core\Model;
use Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Import extends Model
{
    protected string $table = 'import_logs';

    private array $expectedHeaders = [
        'Last Name', 'First Name', 'Middle Name', 'Suffix',
        'Date of Birth', 'Sex', 'Barangay', 'Purok/Zone',
        'Household No.', 'InCode', "Mother's Name", "Father's Name",
        'Contact Number', 'Income Classification', 'Monthly Household Income',
        '4Ps Member', 'NHTS-PR Status', 'PhilHealth Status',
        'Assessment Date', 'Weight (kg)', 'Height (cm)', 'MUAC (cm)',
    ];

    public function processOptPlus(string $filepath): array
    {
        ini_set('memory_limit', '256M');
        $rows = [];
        try {
            $reader = IOFactory::createReaderForFile($filepath);
            $reader->setReadDataOnly(true);
            // Find the correct sheet name before loading
            $sheetNames  = $reader->listWorksheetNames($filepath);
            $targetSheet = null;
            foreach ($sheetNames as $name) {
                if (stripos($name, 'Nut_Status') !== false) { $targetSheet = $name; break; }
            }
            if (!$targetSheet) {
                // Fall back: pick first sheet that isn't Instructions/Summary
                foreach ($sheetNames as $name) {
                    if (!preg_match('/^(Instructions?|Summary|BNS|Clean|List|Region|Duplicate|Print)/i', $name)) {
                        $targetSheet = $name; break;
                    }
                }
            }
            if (!$targetSheet) $targetSheet = $sheetNames[0];

            $reader->setLoadSheetsOnly([$targetSheet]);
            $reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
                public function readCell($columnAddress, $row, $worksheetName = '') {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnAddress);
                    return $col <= 10 && $row <= 1010; // A–J, up to 1000 data rows
                }
            });
            $spreadsheet = $reader->load($filepath);
            $sheet       = $spreadsheet->getActiveSheet();
        } catch (\Exception $e) {
            return ['rows' => [], 'errors' => ['Could not read file: ' . $e->getMessage()]];
        }

        // Barangay is in B3 (user-selected dropdown next to "Barangay" label in A3)
        $barangay = trim((string)$sheet->getCellByColumnAndRow(2, 3)->getValue());
        // Fallback: scan rows 1-9 for a "Barangay" label and check adjacent cell
        if (empty($barangay)) {
            for ($r = 1; $r <= 9; $r++) {
                for ($c = 1; $c <= 10; $c++) {
                    $val = trim((string)$sheet->getCellByColumnAndRow($c, $r)->getValue());
                    if (strtolower($val) === 'barangay') {
                        $next = trim((string)$sheet->getCellByColumnAndRow($c + 1, $r)->getValue());
                        if ($next && !preg_match('/^(barangay|city|municipality|choose)/i', $next)) {
                            $barangay = $next;
                            break 2;
                        }
                    }
                }
            }
        }

        // Data starts at row 10 in OPT Plus (Nut_StatusTool sheet)
        $maxRow       = $sheet->getHighestRow();
        $dataStartRow = 10;

        $beneficiaryModel = new Beneficiary();

        for ($i = $dataStartRow; $i <= $maxRow; $i++) {
            $purok      = trim((string)$sheet->getCellByColumnAndRow(2, $i)->getValue());
            $motherName = trim((string)$sheet->getCellByColumnAndRow(3, $i)->getValue());
            $childName  = trim((string)$sheet->getCellByColumnAndRow(4, $i)->getValue());
            $isIP       = strtolower(trim((string)$sheet->getCellByColumnAndRow(5, $i)->getValue())) === 'yes';
            $sexRaw     = strtoupper(trim((string)$sheet->getCellByColumnAndRow(6, $i)->getValue()));
            $sex        = $sexRaw === 'F' ? 'Female' : 'Male';
            $dobRaw     = $sheet->getCellByColumnAndRow(7, $i)->getValue();
            $dateRaw    = $sheet->getCellByColumnAndRow(8, $i)->getValue();
            $weight     = $sheet->getCellByColumnAndRow(9, $i)->getValue();
            $height     = $sheet->getCellByColumnAndRow(10, $i)->getValue();

            if (empty($childName)) continue;

            // Parse child name: "Surname, First Name"
            if (str_contains($childName, ',')) {
                [$lastName, $firstName] = explode(',', $childName, 2);
            } else {
                $parts     = explode(' ', $childName, 2);
                $lastName  = $parts[0];
                $firstName = $parts[1] ?? '';
            }

            $rowData = [
                'last_name'                => trim($lastName),
                'first_name'               => trim($firstName),
                'middle_name'              => null,
                'suffix'                   => null,
                'date_of_birth'            => $this->parseOptPlusDate($dobRaw),
                'sex'                      => $sex,
                'barangay'                 => $barangay,
                'purok_zone'               => $purok ?: null,
                'household_no'             => null,
                'incode'                   => null,
                'mother_name'              => $motherName ?: null,
                'father_name'              => null,
                'contact_number'           => null,
                'income_classification'    => null,
                'household_monthly_income' => null,
                'is_4ps_member'            => 0,
                'nhts_pr_status'           => null,
                'philhealth_status'        => null,
                'assessment_date'          => $this->parseOptPlusDate($dateRaw),
                'weight_kg'                => is_numeric($weight) ? (float)$weight : null,
                'height_cm'                => is_numeric($height) ? (float)$height : null,
                'muac_cm'                  => null,
                'is_indigenous_people'     => $isIP ? 1 : 0,
            ];

            $rowError = $this->validateRow($rowData);
            $existing = $beneficiaryModel->findDuplicates(
                $rowData['last_name'], $rowData['first_name'],
                $rowData['date_of_birth'], $rowData['barangay']
            );
            $rows[] = [
                'data'     => $rowData,
                'error'    => $rowError,
                'status'   => $rowError ? 'error' : ($existing ? 'update' : 'new'),
                'existing' => $existing[0] ?? null,
                'rowNum'   => $i,
            ];
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return ['rows' => $rows, 'errors' => []];
    }

    private function parseOptPlusDate($value): string
    {
        if (empty($value) && $value !== 0) return '';

        // Excel serial number
        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        $value = trim((string)$value);
        if (empty($value)) return '';

        // "Mar-23-2021" format
        $d = \DateTime::createFromFormat('M-d-Y', $value);
        if ($d) return $d->format('Y-m-d');

        // "Mar-23-21" short year
        $d = \DateTime::createFromFormat('M-d-y', $value);
        if ($d) return $d->format('Y-m-d');

        // Standard YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;

        return '';
    }

    public function processExcel(string $filepath): array
    {
        ini_set('memory_limit', '256M');
        $rows = []; $errors = [];
        try {
            $reader = IOFactory::createReaderForFile($filepath);
            $reader->setReadDataOnly(true);
            $reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
                public function readCell($columnAddress, $row, $worksheetName = '') {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnAddress);
                    return $col <= 22 && $row <= 3000; // A–V, up to 3000 rows
                }
            });
            $spreadsheet = $reader->load($filepath);
            $data        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (\Exception $e) {
            return ['rows' => [], 'errors' => ['Could not read file: ' . $e->getMessage()]];
        }
        if (empty($data)) return ['rows' => [], 'errors' => ['The uploaded file is empty.']];

        $headers = array_map('trim', (array)($data[0] ?? []));
        foreach ($this->expectedHeaders as $i => $expected) {
            $actual = $headers[$i] ?? '';
            if (strtolower($actual) !== strtolower($expected)) {
                $errors[] = "Column " . chr(65 + $i) . " mismatch. Expected \"{$expected}\", got \"{$actual}\".";
            }
        }
        if (!empty($errors)) return ['rows' => [], 'errors' => $errors];

        $beneficiaryModel = new Beneficiary();
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            if (empty(array_filter($row))) continue;
            $rowData  = $this->mapRow($row);
            $rowError = $this->validateRow($rowData);
            $existing = $beneficiaryModel->findDuplicates(
                $rowData['last_name'], $rowData['first_name'],
                $rowData['date_of_birth'], $rowData['barangay']
            );
            $rows[] = [
                'data'     => $rowData,
                'error'    => $rowError,
                'status'   => $rowError ? 'error' : ($existing ? 'update' : 'new'),
                'existing' => $existing[0] ?? null,
                'rowNum'   => $i + 1,
            ];
        }
        return ['rows' => $rows, 'errors' => []];
    }

    private function mapRow(array $row): array
    {
        return [
            'last_name'                => trim((string)($row[0] ?? '')),
            'first_name'               => trim((string)($row[1] ?? '')),
            'middle_name'              => trim((string)($row[2] ?? '')),
            'suffix'                   => trim((string)($row[3] ?? '')),
            'date_of_birth'            => $this->normalizeDate((string)($row[4] ?? '')),
            'sex'                      => trim((string)($row[5] ?? '')),
            'barangay'                 => trim((string)($row[6] ?? '')),
            'purok_zone'               => trim((string)($row[7] ?? '')),
            'household_no'             => trim((string)($row[8] ?? '')),
            'incode'                   => trim((string)($row[9] ?? '')),
            'mother_name'              => trim((string)($row[10] ?? '')),
            'father_name'              => trim((string)($row[11] ?? '')),
            'contact_number'           => trim((string)($row[12] ?? '')),
            'income_classification'    => (function($v) {
                $v = trim((string)$v);
                return in_array($v, ['Poor','Near Poor','Non-Poor']) ? $v : null;
            })($row[13] ?? ''),
            'household_monthly_income' => is_numeric($row[14] ?? '') ? (float)$row[14] : null,
            'is_4ps_member'            => strtolower((string)($row[15] ?? '')) === 'yes' ? 1 : 0,
            'nhts_pr_status'           => (function($v) {
                $v = trim((string)$v);
                return in_array($v, ['Poor','Not Poor']) ? $v : null;
            })($row[16] ?? ''),
            'philhealth_status'        => (function($v) {
                $v = trim((string)$v);
                return in_array($v, ['Member','Indigent','Non-member']) ? $v : null;
            })($row[17] ?? ''),
            'assessment_date'          => $this->normalizeDate((string)($row[18] ?? '')),
            'weight_kg'                => is_numeric($row[19] ?? '') ? (float)$row[19] : null,
            'height_cm'                => is_numeric($row[20] ?? '') ? (float)$row[20] : null,
            'muac_cm'                  => is_numeric($row[21] ?? '') ? (float)$row[21] : null,
        ];
    }

    public function validateRow(array $row): ?string
    {
        if (empty($row['last_name']))    return 'Last Name is required.';
        if (empty($row['first_name']))   return 'First Name is required.';
        if (empty($row['date_of_birth'])) return 'Date of Birth is required.';
        if (!$this->isValidDate($row['date_of_birth'])) return 'Date of Birth is invalid.';
        $ageMonths = \DateHelper::ageInMonths($row['date_of_birth']);
        if ($ageMonths < 0)  return 'Date of Birth cannot be in the future.';
        if ($ageMonths > 59) return "Child is {$ageMonths} months old — only 0–59 months are accepted.";
        if (!in_array($row['sex'], ['Male','Female'])) return 'Sex must be Male or Female.';
        if (empty($row['barangay']))     return 'Barangay is required.';
        if (empty($row['weight_kg']))    return 'Weight is required.';
        if (!empty($row['assessment_date']) && !$this->isValidDate($row['assessment_date'])) {
            return 'Assessment Date is invalid.';
        }
        return null;
    }

    public function executeImport(array $rows, int $importedBy, string $origFilename = '', ?string $savedFilename = null, ?string $folder = null, string $source = 'Excel'): array
    {
        $success = 0; $errors = [];
        $db = Database::getInstance();
        $beneficiaryModel = new Beneficiary();
        $assessmentModel  = new Assessment();
        $enrollmentModel  = new ProgramEnrollment();

        $db->beginTransaction();
        try {
            foreach ($rows as $row) {
                if ($row['error']) { $errors[] = "Row {$row['rowNum']}: " . $row['error']; continue; }
                $data = $row['data'];

                if ($row['existing']) {
                    $beneficiaryId = $row['existing']['id'];
                    $beneficiaryModel->update($beneficiaryId, array_merge($this->toBeneficiaryFields($data), [
                        'source' => $source, 'validation_status' => 'validated',
                    ]));
                } else {
                    $beneficiaryId = $beneficiaryModel->insert(array_merge($this->toBeneficiaryFields($data), [
                        'created_by' => $importedBy, 'source' => $source, 'validation_status' => 'validated',
                    ]));
                }

                if (!empty($data['weight_kg']) && !empty($data['assessment_date'])) {
                    $ageMonths    = \DateHelper::ageInMonths($data['date_of_birth'], $data['assessment_date']);
                    $period       = (int)date('n', strtotime($data['assessment_date'])) <= 6 ? 'January' : 'July';
                    $year         = (int)date('Y', strtotime($data['assessment_date']));

                    // Skip if an assessment already exists for this beneficiary this year (OPT is once a year)
                    $dupChk = $db->prepare("SELECT id FROM assessments WHERE beneficiary_id = ? AND assessment_year = ? LIMIT 1");
                    $dupChk->execute([$beneficiaryId, $year]);
                    if ($dupChk->fetch()) {
                        $success++;
                        continue;
                    }

                    $assessmentId = $assessmentModel->createWithZScore([
                        'beneficiary_id'  => $beneficiaryId,
                        'assessment_date' => $data['assessment_date'],
                        'age_in_months'   => $ageMonths,
                        'weight_kg'       => $data['weight_kg'],
                        'height_cm'       => $data['height_cm'],
                        'muac_cm'         => $data['muac_cm'],
                        'sex'             => $data['sex'],
                        'period'          => $period,
                        'assessment_year' => $year,
                        'created_by'      => $importedBy,
                    ]);
                    $enrollmentModel->autoEnrollDSP($assessmentId);
                }
                $success++;
            }

            $this->insert([
                'filename'       => $origFilename ?: null,
                'saved_filename' => $savedFilename,
                'folder'         => $folder,
                'imported_by'       => $importedBy,
                'import_date'       => date('Y-m-d H:i:s'),
                'total_rows'        => count($rows),
                'success_count'     => $success,
                'error_count'       => count($errors),
                'error_details'     => json_encode($errors),
            ]);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
        return ['success' => $success, 'errors' => $errors];
    }

    public function getImportLogs(array $filters = [], string $sort = 'import_date', string $dir = 'DESC'): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[]  = 'il.import_date >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'il.import_date <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['imported_by'])) {
            $where[]  = 'u.full_name LIKE :imported_by';
            $params[':imported_by'] = '%' . $filters['imported_by'] . '%';
        }
        if (!empty($filters['folder'])) {
            if ($filters['folder'] === '__none__') {
                $where[] = 'il.folder IS NULL';
            } else {
                $where[]  = 'il.folder = :folder';
                $params[':folder'] = $filters['folder'];
            }
        }

        $allowedSort = [
            'import_date'      => 'il.import_date',
            'imported_by_name' => 'u.full_name',
            'folder'           => 'il.folder',
            'total_rows'       => 'il.total_rows',
            'success_count'    => 'il.success_count',
            'error_count'      => 'il.error_count',
        ];
        $orderBy = ($allowedSort[$sort] ?? 'il.import_date') . ' ' . $dir;

        $sql = "SELECT il.*, u.full_name AS imported_by_name
                FROM import_logs il
                LEFT JOIN users u ON u.id = il.imported_by"
             . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
             . " ORDER BY {$orderBy} LIMIT 200";

        return $this->fetchAll($sql, $params);
    }

    public function getImportLogById(int $id): ?array
    {
        $rows = $this->fetchAll(
            "SELECT * FROM import_logs WHERE id = :id LIMIT 1",
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }

    public function deleteLog(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM import_logs WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function getStorageContents(string $folder = ''): array
    {
        $dir = IMPORT_STORAGE_PATH . ($folder !== '' ? '/' . $folder : '');
        if (!is_dir($dir)) return [];

        $files = [];
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $fullPath = $dir . '/' . $entry;
            if (!is_file($fullPath)) continue;

            // Look up DB record for this file
            $logs = $this->fetchAll(
                "SELECT il.*, u.full_name AS imported_by_name
                 FROM import_logs il
                 LEFT JOIN users u ON u.id = il.imported_by
                 WHERE il.saved_filename = :fn
                   AND " . ($folder !== '' ? "il.folder = :folder" : "il.folder IS NULL") . "
                 LIMIT 1",
                $folder !== ''
                    ? [':fn' => $entry, ':folder' => $folder]
                    : [':fn' => $entry]
            );
            $log = $logs[0] ?? null;

            $files[] = [
                'saved_filename'    => $entry,
                'original_filename' => $log['filename'] ?? $entry,
                'size'              => filesize($fullPath),
                'modified'          => filemtime($fullPath),
                'import_date'       => $log['import_date'] ?? null,
                'imported_by_name'  => $log['imported_by_name'] ?? '—',
                'total_rows'        => $log['total_rows'] ?? '—',
                'success_count'     => $log['success_count'] ?? '—',
                'error_count'       => $log['error_count'] ?? '—',
                'log_id'            => $log['id'] ?? null,
                'folder'            => $folder,
            ];
        }

        usort($files, fn($a, $b) => $b['modified'] - $a['modified']);
        return $files;
    }

    public function getImportFolders(): array
    {
        $storageDir = IMPORT_STORAGE_PATH;
        if (!is_dir($storageDir)) return [];

        $folders = [];
        foreach (scandir($storageDir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            if (is_dir($storageDir . '/' . $entry)) {
                $fileCount = count(array_diff(scandir($storageDir . '/' . $entry), ['.', '..']));
                $folders[] = ['name' => $entry, 'file_count' => $fileCount];
            }
        }
        usort($folders, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $folders;
    }

    private function toBeneficiaryFields(array $data): array
    {
        return [
            'last_name'                => $data['last_name'],
            'first_name'               => $data['first_name'],
            'middle_name'              => $data['middle_name'] ?: null,
            'suffix'                   => $data['suffix'] ?: null,
            'date_of_birth'            => $data['date_of_birth'],
            'sex'                      => $data['sex'],
            'barangay'                 => $data['barangay'],
            'purok_zone'               => $data['purok_zone'] ?: null,
            'household_no'             => $data['household_no'] ?: null,
            'incode'                   => $data['incode'] ?: null,
            'mother_name'              => $data['mother_name'] ?: null,
            'father_name'              => $data['father_name'] ?: null,
            'contact_number'           => $data['contact_number'] ?: null,
            'income_classification'    => $data['income_classification'] ?: null,
            'household_monthly_income' => $data['household_monthly_income'],
            'is_4ps_member'            => $data['is_4ps_member'],
            'nhts_pr_status'           => $data['nhts_pr_status'] ?: null,
            'philhealth_status'        => $data['philhealth_status'] ?: null,
        ];
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        if (empty($value)) return '';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;
        if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $value)) {
            $d = \DateTime::createFromFormat('m/d/Y', $value);
            if ($d) return $d->format('Y-m-d');
        }
        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {}
        }
        return $value;
    }

    private function isValidDate(string $date): bool
    {
        if (empty($date)) return false;
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
