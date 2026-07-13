<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use App\Models\DispensingRecord;
use App\Models\Beneficiary;
use App\Models\ProgramEnrollment;
use App\Models\Program;

class DispensingController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $year          = (int)($_GET['year']           ?? date('Y'));
        $program       = $_GET['program']              ?? '';
        $barangay      = in_array(Session::get('user_role'), ['bhw', 'bns'])
                         ? Session::get('user_barangay')
                         : ($_GET['barangay'] ?? '');
        $beneficiaryId = (int)($_GET['beneficiary_id'] ?? 0);
        $selectedMonth = (int)($_GET['month']          ?? date('n'));

        $records   = [];
        $summary   = [];
        $dbError   = null;

        try {
            $model     = new DispensingRecord();
            $records   = $model->getAll($year, $program, $barangay, $beneficiaryId);
            $summary   = $model->getSummary($year);
        } catch (\Throwable $e) {
            $dbError = 'The dispensing_records table does not exist yet. Please run the database migration: <code>php database/run_migrations.php</code>';
        }

        // Monthly MNP & LNS-SQ log
        $db       = Database::getInstance();
        $mWhere   = "m.year = ? AND MONTH(m.date_given) = ?";
        $lWhere   = "l.year = ? AND MONTH(l.date_given) = ?";
        $mParams  = [$year, $selectedMonth];
        $lParams  = [$year, $selectedMonth];
        if ($barangay) {
            $mWhere .= ' AND b.barangay = ?'; $mParams[] = $barangay;
            $lWhere .= ' AND b.barangay = ?'; $lParams[] = $barangay;
        }
        $stmt = $db->prepare(
            "SELECT m.*, b.last_name, b.first_name, b.barangay, b.date_of_birth
             FROM mnp_records m JOIN beneficiaries b ON b.id = m.beneficiary_id
             WHERE $mWhere ORDER BY m.date_given, b.last_name"
        );
        $stmt->execute($mParams);
        $mnpMonthRecords = $stmt->fetchAll();

        $stmt = $db->prepare(
            "SELECT l.*, b.last_name, b.first_name, b.barangay, b.date_of_birth
             FROM lns_sq_records l JOIN beneficiaries b ON b.id = l.beneficiary_id
             WHERE $lWhere ORDER BY l.date_given, b.last_name"
        );
        $stmt->execute($lParams);
        $lnsMonthRecords = $stmt->fetchAll();

        $beneficiaryModel = new Beneficiary();
        $barangays        = $beneficiaryModel->getAllBarangays();
        $allBeneficiaries = Database::getInstance()->query(
            "SELECT id, last_name, first_name, barangay
             FROM beneficiaries WHERE deleted_at IS NULL
             ORDER BY last_name, first_name"
        )->fetchAll();
        $activePrograms   = (new Program())->getActive();

        $this->view('dispensing/index', [
            'records'          => $records,
            'summary'          => $summary,
            'year'             => $year,
            'program'          => $program,
            'barangay'         => $barangay,
            'beneficiaryId'    => $beneficiaryId,
            'barangays'        => $barangays,
            'allBeneficiaries' => $allBeneficiaries,
            'activePrograms'   => $activePrograms,
            'isBhw'            => in_array(Session::get('user_role'), ['bhw', 'bns']),
            'dbError'          => $dbError,
            'selectedMonth'    => $selectedMonth,
            'mnpMonthRecords'  => $mnpMonthRecords,
            'lnsMonthRecords'  => $lnsMonthRecords,
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->requirePermission('dispensing');

        $beneficiaryId = (int)($_GET['bid'] ?? 0);
        $beneficiary   = null;
        $enrollments   = [];

        if ($beneficiaryId) {
            $beneficiary = (new Beneficiary())->findById($beneficiaryId);
            $enrollments = (new ProgramEnrollment())->findByBeneficiary($beneficiaryId);
        }

        if ($this->isPost()) {
            $this->validateCsrf();

            $bid = (int)($_POST['beneficiary_id'] ?? 0);
            $data = [
                'beneficiary_id'  => $bid,
                'enrollment_id'   => !empty($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : null,
                'program'         => trim($_POST['program']         ?? ''),
                'supplement_type' => trim($_POST['supplement_type'] ?? ''),
                'quantity'        => (float)($_POST['quantity']     ?? 1),
                'unit'            => trim($_POST['unit']            ?? 'piece(s)'),
                'date_dispensed'  => $_POST['date_dispensed']       ?? date('Y-m-d'),
                'dispensed_by'    => Session::get('user_id'),
                'notes'           => trim($_POST['notes']           ?? ''),
            ];

            if ($data['supplement_type'] === 'Other' && !empty($_POST['supplement_type_custom'])) {
                $data['supplement_type'] = htmlspecialchars(trim($_POST['supplement_type_custom']), ENT_QUOTES, 'UTF-8');
            }

            if (!$data['beneficiary_id'] || !$data['program'] || !$data['supplement_type']) {
                Session::flash('error', 'Beneficiary, program, and supplement type are required.');
                $this->redirect('/dispensing/create' . ($bid ? "?bid={$bid}" : ''));
                return;
            }

            try {
                (new DispensingRecord())->recordDispensing($data);
                \ActivityLog::log('dispensing_create',
                    "Dispensed {$data['supplement_type']} ({$data['quantity']} {$data['unit']}) to beneficiary #{$data['beneficiary_id']}"
                );
                Session::flash('success', 'Dispensing record saved.');
            } catch (\Throwable $e) {
                Session::flash('error', 'Could not save record. The dispensing_records table may not exist yet. Run migrations first.');
            }

            $this->redirect($bid ? "/beneficiaries/{$bid}" : '/dispensing');
        }

        $allBeneficiaries = Database::getInstance()->query(
            "SELECT id, last_name, first_name, barangay
             FROM beneficiaries WHERE deleted_at IS NULL
             ORDER BY last_name, first_name"
        )->fetchAll();
        $activePrograms   = (new Program())->getActive();

        $this->view('dispensing/create', compact(
            'beneficiary', 'enrollments', 'allBeneficiaries', 'beneficiaryId', 'activePrograms'
        ));
    }

    public function export(): void
    {
        $this->requireAuth();

        $year          = (int)($_GET['year']           ?? date('Y'));
        $program       = $_GET['program']              ?? '';
        $barangay      = in_array(Session::get('user_role'), ['bhw', 'bns'])
                         ? Session::get('user_barangay')
                         : ($_GET['barangay'] ?? '');
        $beneficiaryId = (int)($_GET['beneficiary_id'] ?? 0);
        $format        = $_GET['format']               ?? 'excel';

        try {
            $records = (new DispensingRecord())->getAll($year, $program, $barangay, $beneficiaryId);
        } catch (\Throwable $e) {
            Session::flash('error', 'The dispensing_records table does not exist yet. Please run migrations.');
            $this->redirect('/dispensing');
        }
        $headers  = ['Barangay','Last Name','First Name','DOB','Program',
                     'Supplement/Medicine','Quantity','Unit','Date Dispensed','Dispensed By','Notes'];
        $filename = 'Dispensing_Report_' . $year . ($program ? "_$program" : '') . ($barangay ? "_$barangay" : '');

        if ($format === 'pdf') {
            $this->exportPdf($records, $headers, $filename, $year, $program, $barangay);
            return;
        }

        // Default: Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dispensing Records');

        foreach ($headers as $col => $h) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D6E4F0');
        }

        foreach ($records as $ri => $row) {
            $cells = [
                $row['barangay'], $row['last_name'], $row['first_name'], $row['date_of_birth'],
                $row['program'], $row['supplement_type'], $row['quantity'], $row['unit'],
                $row['date_dispensed'], $row['dispensed_by_name'] ?? '', $row['notes'] ?? '',
            ];
            foreach ($cells as $ci => $val) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1) . ($ri + 2);
                $sheet->setCellValue($cell, $val);
            }
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}.xlsx\"");
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    private function exportPdf(array $records, array $headers, string $filename,
                                int $year, string $program, string $barangay): void
    {
        $title = 'Medicine &amp; Supplement Dispensing Report — ' . $year;
        if ($program)  $title .= " — $program";
        if ($barangay) $title .= " — $barangay";

        $html  = '<html><head><style>
            body{font-family:DejaVu Sans,sans-serif;font-size:9px;}
            h2{font-size:12px;margin-bottom:6px;}
            table{width:100%;border-collapse:collapse;}
            th{background:#2563eb;color:white;padding:4px 6px;text-align:left;}
            td{padding:3px 6px;border-bottom:1px solid #e5e7eb;}
            tr:nth-child(even) td{background:#f8fafc;}
        </style></head><body>';
        $html .= "<h2>{$title}</h2>";
        $html .= '<p style="font-size:8px;color:#6b7280;">Generated: ' . date('F j, Y H:i')
               . ' &nbsp;|&nbsp; Total: ' . count($records) . ' records</p>';
        $html .= '<table><tr>';
        foreach ($headers as $h) $html .= '<th>' . htmlspecialchars($h) . '</th>';
        $html .= '</tr>';

        foreach ($records as $row) {
            $cells = [
                $row['barangay'], $row['last_name'], $row['first_name'], $row['date_of_birth'],
                $row['program'], $row['supplement_type'], $row['quantity'], $row['unit'],
                $row['date_dispensed'], $row['dispensed_by_name'] ?? '', $row['notes'] ?? '',
            ];
            $html .= '<tr>' . implode('', array_map(
                fn($c) => '<td>' . htmlspecialchars((string)$c) . '</td>', $cells
            )) . '</tr>';
        }

        $html .= '</table></body></html>';

        $dompdf = new \Dompdf\Dompdf(['defaultPaperSize' => 'legal', 'defaultPaperOrientation' => 'landscape']);
        $dompdf->loadHtml($html);
        $dompdf->render();
        $dompdf->stream($filename . '.pdf', ['Attachment' => true]);
        exit;
    }

}
