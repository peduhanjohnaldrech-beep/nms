<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use App\Models\Assessment;
use App\Models\Beneficiary;
use App\Helpers\EoptExport;

class ReportController extends Controller
{
    private function allowedRoles(): array
    {
        return ['admin', 'nutritionist', 'encoder', 'bhw', 'bns'];
    }

    private function resolveBarangay(string $requested): string
    {
        if (in_array(Session::get('user_role'), ['bhw', 'bns'])) {
            return Session::get('user_barangay', '');
        }
        return $requested;
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->requirePermission('reports');
        $this->view('reports/index');
    }

    public function opt(): void
    {
        $this->requireAuth();
        $this->requirePermission('reports');

        $year     = (int)($_GET['year'] ?? date('Y'));
        $period   = $_GET['period']   ?? '';
        $barangay = $this->resolveBarangay($_GET['barangay'] ?? '');
        $source   = $_GET['source']   ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to']   ?? '';

        $ageGroupStats = (new Assessment())->getAgeGroupStats($year, $period, $barangay);

        $this->view('reports/opt', array_merge(
            ['rows' => $this->getReportData('opt', $year, $period, $barangay, $source, $dateFrom, $dateTo)],
            ['year' => $year, 'period' => $period, 'barangay' => $barangay, 'source' => $source,
             'dateFrom' => $dateFrom, 'dateTo' => $dateTo,
             'isBhw' => in_array(Session::get('user_role'), ['bhw', 'bns']),
             'ageGroupStats' => $ageGroupStats,
             'barangays' => (new Beneficiary())->getAllBarangays()]
        ));
    }

    public function dsp(): void
    {
        $this->requireAuth();
        $this->requirePermission('reports');

        $year     = (int)($_GET['year'] ?? date('Y'));
        $barangay = $this->resolveBarangay($_GET['barangay'] ?? '');
        $source   = $_GET['source']   ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to']   ?? '';

        $this->view('reports/dsp', [
            'rows'      => $this->getReportData('dsp', $year, '', $barangay, $source, $dateFrom, $dateTo),
            'year'      => $year,
            'barangay'  => $barangay,
            'source'    => $source,
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
            'isBhw'     => in_array(Session::get('user_role'), ['bhw', 'bns']),
            'barangays' => (new Beneficiary())->getAllBarangays(),
        ]);
    }

    public function mns(): void
    {
        $this->requireAuth();
        $this->requirePermission('reports');

        $year     = (int)($_GET['year'] ?? date('Y'));
        $round    = $_GET['round']     ?? '';
        $barangay = $this->resolveBarangay($_GET['barangay'] ?? '');
        $source   = $_GET['source']    ?? '';
        $tab      = $_GET['tab']       ?? 'vita';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to']   ?? '';

        $exportType = match($tab) {
            'mnp'     => 'mns_mnp',
            'lns'     => 'mns_lns',
            'monthly' => '',
            default   => 'mns_vita',
        };

        $rows = $tab !== 'monthly'
            ? $this->getReportData($exportType, $year, $round, $barangay, $source, $dateFrom, $dateTo)
            : [];

        // Monthly log data
        $selectedMonth   = (int)($_GET['month'] ?? date('n'));
        $mnpMonthRecords = [];
        $lnsMonthRecords = [];

        if ($tab === 'monthly') {
            $db     = Database::getInstance();
            $monthPad = str_pad($selectedMonth, 2, '0', STR_PAD_LEFT);

            $mWhere = "m.year = ? AND MONTH(m.date_given) = ?";
            $mParams = [$year, $selectedMonth];
            $lWhere  = "l.year = ? AND MONTH(l.date_given) = ?";
            $lParams = [$year, $selectedMonth];
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
        }

        $this->view('reports/mns', [
            'rows'            => $rows,
            'year'            => $year,
            'round'           => $round,
            'barangay'        => $barangay,
            'source'          => $source,
            'dateFrom'        => $dateFrom,
            'dateTo'          => $dateTo,
            'tab'             => $tab,
            'exportType'      => $exportType,
            'isBhw'           => in_array(Session::get('user_role'), ['bhw', 'bns']),
            'selectedMonth'   => $selectedMonth,
            'mnpMonthRecords' => $mnpMonthRecords,
            'lnsMonthRecords' => $lnsMonthRecords,
            'barangays'       => (new Beneficiary())->getAllBarangays(),
        ]);
    }

    public function outcome(): void
    {
        $this->requireAuth();
        $this->requirePermission('reports');

        $year     = (int)($_GET['year'] ?? date('Y'));
        $barangay = $this->resolveBarangay($_GET['barangay'] ?? '');

        $db     = Database::getInstance();
        $where  = ['pe.program = \'DSP\'', 'pe.cycle_year = ?'];
        $params = [$year];
        if ($barangay) { $where[] = 'b.barangay = ?'; $params[] = $barangay; }

        $stmt = $db->prepare(
            "SELECT pe.*, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.id AS beneficiary_id
             FROM program_enrollments pe JOIN beneficiaries b ON b.id = pe.beneficiary_id
             WHERE " . implode(' AND ', $where) . " ORDER BY b.barangay, b.last_name"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $barangays = (new Beneficiary())->getAllBarangays();

        $this->view('reports/outcome', [
            'rows'      => $rows,
            'year'      => $year,
            'barangay'  => $barangay,
            'barangays' => $barangays,
            'isBhw'     => in_array(Session::get('user_role'), ['bhw', 'bns']),
        ]);
    }

    public function comparison(): void
    {
        $this->requireAuth();
        $this->requirePermission('reports');

        $year     = (int)($_GET['year']   ?? date('Y'));
        $barangay = $this->resolveBarangay($_GET['barangay'] ?? '');
        $db       = Database::getInstance();

        $params = [$year];
        $bWhere = '';
        if ($barangay) { $bWhere = ' AND b.barangay = ?'; $params[] = $barangay; }

        $stmt = $db->prepare(
            "SELECT b.barangay, a.period, a.nutritional_status, COUNT(*) as cnt
             FROM assessments a JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL AND a.assessment_year = ?$bWhere
               AND a.period IN ('January','July')
             GROUP BY b.barangay, a.period, a.nutritional_status
             ORDER BY b.barangay, a.period"
        );
        $stmt->execute($params);

        $raw = [];
        foreach ($stmt->fetchAll() as $r) {
            $raw[$r['barangay']][$r['period']][$r['nutritional_status']] = (int)$r['cnt'];
        }

        $allBarangays = array_keys($raw);
        sort($allBarangays);

        $rows = [];
        foreach ($allBarangays as $bar) {
            $jan = $raw[$bar]['January'] ?? [];
            $jul = $raw[$bar]['July']    ?? [];
            $janTotal  = array_sum($jan);
            $julTotal  = array_sum($jul);
            $janMalnut = ($jan['SUW'] ?? 0) + ($jan['UW'] ?? 0);
            $julMalnut = ($jul['SUW'] ?? 0) + ($jul['UW'] ?? 0);
            $janRate   = $janTotal > 0 ? round($janMalnut / $janTotal * 100, 1) : null;
            $julRate   = $julTotal > 0 ? round($julMalnut / $julTotal * 100, 1) : null;
            $rows[] = [
                'barangay'  => $bar,
                'jan'       => $jan, 'janTotal' => $janTotal, 'janMalnut' => $janMalnut, 'janRate' => $janRate,
                'jul'       => $jul, 'julTotal' => $julTotal, 'julMalnut' => $julMalnut, 'julRate' => $julRate,
                'change'    => ($janRate !== null && $julRate !== null) ? round($julRate - $janRate, 1) : null,
            ];
        }

        $barangays = (new Beneficiary())->getAllBarangays();
        $this->view('reports/comparison', compact('rows', 'year', 'barangay', 'barangays'));
    }

    public function summary(): void
    {
        $this->requireAuth();
        $this->requirePermission('reports');

        $year     = (int)($_GET['year']   ?? date('Y'));
        $period   = $_GET['period']       ?? '';
        $barangay = $this->resolveBarangay($_GET['barangay'] ?? '');
        $db       = Database::getInstance();

        $bWhere  = $barangay ? ' AND b.barangay = ?' : '';
        $bParams = $barangay ? [$barangay] : [];

        // Total active (0-59 months) beneficiaries per barangay
        // Total eligible: children who were 0–59 months at any point during the selected year
        $stmt = $db->prepare(
            "SELECT barangay, COUNT(*) AS total
             FROM beneficiaries
             WHERE deleted_at IS NULL
               AND date_of_birth <= '{$year}-12-31'
               AND date_of_birth >= DATE_SUB('{$year}-01-01', INTERVAL 59 MONTH)"
            . ($barangay ? " AND barangay = ?" : "") .
            " GROUP BY barangay ORDER BY barangay"
        );
        $stmt->execute($bParams);
        $totalByBar = [];
        foreach ($stmt->fetchAll() as $r) $totalByBar[$r['barangay']] = (int)$r['total'];

        // Distinct children assessed per barangay (not assessment count)
        $pWhere  = $bWhere . " AND a.assessment_year = ?";
        $pParams = array_merge($bParams, [$year]);
        if ($period) { $pWhere .= ' AND a.period = ?'; $pParams[] = $period; }
        $stmt = $db->prepare(
            "SELECT b.barangay, a.nutritional_status, COUNT(*) AS cnt
             FROM assessments a JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL$pWhere
             GROUP BY b.barangay, a.nutritional_status ORDER BY b.barangay"
        );
        $stmt->execute($pParams);
        $statusByBar = [];
        foreach ($stmt->fetchAll() as $r) {
            $statusByBar[$r['barangay']][$r['nutritional_status']] = (int)$r['cnt'];
        }

        // Distinct weighed children per barangay
        $stmt = $db->prepare(
            "SELECT b.barangay, COUNT(DISTINCT a.beneficiary_id) AS cnt
             FROM assessments a JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL$pWhere
             GROUP BY b.barangay"
        );
        $stmt->execute($pParams);
        $weighedByBar = [];
        foreach ($stmt->fetchAll() as $r) $weighedByBar[$r['barangay']] = (int)$r['cnt'];

        // DSP active enrollments per barangay
        $stmt = $db->prepare(
            "SELECT b.barangay, COUNT(*) AS cnt
             FROM program_enrollments pe JOIN beneficiaries b ON b.id = pe.beneficiary_id
             WHERE pe.program = 'DSP' AND pe.status = 'Active'" . $bWhere .
            " GROUP BY b.barangay"
        );
        $stmt->execute($bParams);
        $dspByBar = [];
        foreach ($stmt->fetchAll() as $r) $dspByBar[$r['barangay']] = (int)$r['cnt'];

        // Vitamin A coverage per barangay for the year
        $stmt = $db->prepare(
            "SELECT b.barangay, COUNT(DISTINCT v.beneficiary_id) AS cnt
             FROM vitamin_a_records v JOIN beneficiaries b ON b.id = v.beneficiary_id
             WHERE v.year = ?" . $bWhere .
            " GROUP BY b.barangay"
        );
        $stmt->execute(array_merge([$year], $bParams));
        $vitaByBar = [];
        foreach ($stmt->fetchAll() as $r) $vitaByBar[$r['barangay']] = (int)$r['cnt'];

        // Build summary rows
        $allBarangays = array_unique(array_merge(
            array_keys($totalByBar), array_keys($statusByBar),
            array_keys($dspByBar), array_keys($vitaByBar)
        ));
        sort($allBarangays);

        $summaryRows = [];
        foreach ($allBarangays as $bar) {
            $total    = $totalByBar[$bar] ?? 0;
            $statuses = $statusByBar[$bar] ?? [];
            $assessed = array_sum($statuses);
            $weighed  = $weighedByBar[$bar] ?? 0;
            $suw      = $statuses['SUW']    ?? 0;
            $uw       = $statuses['UW']     ?? 0;
            $normal   = $statuses['Normal'] ?? 0;
            $malnourished = $suw + $uw;
            $summaryRows[] = [
                'barangay'      => $bar,
                'total'         => $total,
                'assessed'      => $weighed,
                'coverage_pct'  => $total > 0 ? min(100, round($weighed / $total * 100, 1)) : 0,
                'suw'           => $suw,
                'uw'            => $uw,
                'normal'        => $normal,
                'malnourished'  => $malnourished,
                'malnut_rate'   => $assessed > 0 ? round($malnourished / $assessed * 100, 1) : 0,
                'dsp_active'    => $dspByBar[$bar] ?? 0,
                'vita_covered'  => $vitaByBar[$bar] ?? 0,
            ];
        }

        $barangays = (new Beneficiary())->getAllBarangays();
        $this->view('reports/summary', [
            'summaryRows' => $summaryRows,
            'year'        => $year,
            'period'      => $period,
            'barangay'    => $barangay,
            'barangays'   => $barangays,
        ]);
    }

    public function distribution(): void
    {
        $this->requireAuth();
        $this->requirePermission('reports');
        $this->view('reports/distribution');
    }

    public function exportEopt(): void
    {
        $this->requireAuth();
        $this->requirePermission('reports');
        ini_set('memory_limit', '512M');

        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            Session::flash('error', 'PhpSpreadsheet is not available.');
            $this->redirect('/reports/opt');
        }

        $tpl = BASE_PATH . '/storage/templates/eopt_slim.xlsx';
        if (!file_exists($tpl)) {
            Session::flash('error', 'eOPT template file not found. Please upload it to storage/templates/.');
            $this->redirect('/reports/opt');
        }

        $year     = (int)($_GET['year']     ?? date('Y'));
        $period   = $_GET['period']          ?? '';
        $barangay = $this->resolveBarangay($_GET['barangay'] ?? '');

        try {
            $export = new EoptExport($year, $period, $barangay);
            $tmp    = $export->generate();
        } catch (\Throwable $e) {
            Session::flash('error', 'eOPT export failed: ' . $e->getMessage());
            $this->redirect('/reports/opt');
        }

        $suffix   = $year . ($period ? '_' . $period : '') . ($barangay ? '_' . preg_replace('/[^A-Za-z0-9]/', '_', $barangay) : '');
        $filename = 'eOPT_' . $suffix . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Content-Length: ' . filesize($tmp));

        readfile($tmp);
        unlink($tmp);
        exit;
    }

    public function export(): void
    {
        $this->requireAuth();
        $this->requirePermission('reports');

        $type     = $_GET['type']    ?? 'opt';
        $format   = $_GET['format']  ?? 'csv';
        $year     = (int)($_GET['year'] ?? date('Y'));
        $period   = $_GET['period']   ?? '';
        $barangay = $this->resolveBarangay($_GET['barangay'] ?? '');
        $source   = $_GET['source']   ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to']   ?? '';

        // Monthly MNS log has its own export handler
        if ($type === 'mns_monthly') {
            $this->exportMonthlyExcel($year, $barangay, (int)($_GET['month'] ?? 0));
            return;
        }

        $rows     = $this->getReportData($type, $year, $period, $barangay, $source, $dateFrom, $dateTo);
        $headers  = $this->getCsvHeaders($type);
        $filenamePrefix = match($type) {
            'opt'       => 'OPT_OperationTimbang',
            'dsp'       => 'DSP_DietarySupplementation',
            'dsp_ready' => 'DSP_ReadyToDischarge',
            'mns_vita'  => 'MNS_VitaminA',
            'mns_mnp'   => 'MNS_MNP',
            'mns_lns'   => 'MNS_LNSSQ',
            default     => strtoupper($type),
        };
        $filename = $filenamePrefix . "_report_{$year}";

        if ($format === 'excel') {
            $this->exportExcel($rows, $headers, $type, $filename);
            return;
        }

        if ($format === 'pdf') {
            $this->exportPdf($rows, $headers, $type, $filename, $year, $period, $barangay);
            return;
        }

        // Default: CSV
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        header('Cache-Control: no-cache, no-store, must-revalidate');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $this->mapRowForCsv($row, $type));
        }
        fclose($out);
        exit;
    }

    private function exportExcel(array $rows, array $headers, string $type, string $filename): void
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            Session::flash('error', 'PhpSpreadsheet is not available.');
            $this->redirect('/reports');
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle(strtoupper($type));

        $colCount = count($headers);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        // Header row styling
        foreach ($headers as $col => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
            $cell      = $colLetter . '1';
            $sheet->setCellValue($cell, $header);
            $style = $sheet->getStyle($cell);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E40AF');
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)->setWrapText(true);
            $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('FFFFFF');
        }
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Data rows
        foreach ($rows as $rowIdx => $row) {
            $excelRow = $rowIdx + 2;
            $rowData  = $this->mapRowForExcel($row, $type);
            $isEven   = $rowIdx % 2 === 0;

            foreach ($rowData as $colIdx => $value) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                $cell      = $colLetter . $excelRow;
                $sheet->setCellValue($cell, $value);
                $style = $sheet->getStyle($cell);
                $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($isEven ? 'F0F4FF' : 'FFFFFF');
                $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
                $style->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }
            $sheet->getRowDimension($excelRow)->setRowHeight(18);
        }

        // Auto-size columns
        foreach (range(1, $colCount) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        // Freeze header row
        $sheet->freezePane('A2');

        // Auto-filter
        $sheet->setAutoFilter("A1:{$lastCol}1");

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}.xlsx\"");
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function formatDate(string $date): string
    {
        return $date ? date('F j, Y', strtotime($date)) : '';
    }

    private function mapRowForExcel(array $row, string $type): array
    {
        return match($type) {
            'opt' => [
                $row['barangay'], $row['last_name'], $row['first_name'],
                $this->formatDate($row['date_of_birth']),
                $row['sex'], $row['age_in_months'],
                $row['weight_kg'], $row['height_cm'] ?? '',
                $row['weight_for_age_zscore'] ?? '', $row['nutritional_status'],
                $row['height_for_age_zscore'] ?? '', $row['hfa_status'] ?? '',
                $row['wflh_zscore'] ?? '', $row['wflh_status'] ?? '',
                $row['muac_cm'] ?? '', $row['period'], $row['assessment_year'],
            ],
            'dsp' => [
                $row['barangay'], $row['last_name'], $row['first_name'],
                $this->formatDate($row['date_of_birth']),
                $this->formatDate($row['enrollment_date']),
                $row['intervention_type'] ?? '',
                $row['pre_weight_kg'] ?? '',
                $row['post_weight_kg'] ?? '',
                (isset($row['pre_weight_kg'], $row['post_weight_kg']) && $row['pre_weight_kg'] > 0 && $row['post_weight_kg'] > 0)
                    ? round($row['post_weight_kg'] - $row['pre_weight_kg'], 2) : '',
                $row['status'],
                $this->formatDate($row['end_date'] ?? ''),
                $row['cycle_year'],
            ],
            'dsp_ready' => [
                $row['barangay'], $row['last_name'], $row['first_name'],
                $this->formatDate($row['date_of_birth']),
                'Cycle ' . $row['cycle_number'],
                $row['intervention_type'] ?? '',
                $this->formatDate($row['enrollment_date']),
                $row['nutritional_status'],
                $row['wflh_status'] ?? 'Normal',
                $this->formatDate($row['assessment_date']),
            ],
            'mns_vita' => [
                $row['barangay'], $row['last_name'], $row['first_name'],
                $this->formatDate($row['date_of_birth']),
                $this->formatDate($row['distribution_date']),
                $row['round'], $row['year'], $row['dosage_iu'],
                $row['capsule_color'], $row['administered_by'] ?? '',
            ],
            'mns_mnp' => [
                $row['barangay'], $row['last_name'], $row['first_name'],
                $this->formatDate($row['date_of_birth']),
                $this->formatDate($row['date_given']),
                $row['year'], $row['age_group'],
                $row['completed_routine'] ? 'Yes' : 'No',
            ],
            'mns_lns' => [
                $row['barangay'], $row['last_name'], $row['first_name'],
                $this->formatDate($row['date_of_birth']),
                $this->formatDate($row['date_given']),
                $row['year'], $row['age_group'],
                $row['completed_routine'] ? 'Yes' : 'No',
            ],
            default => [],
        };
    }

    private function exportPdf(array $rows, array $headers, string $type, string $filename, int $year, string $period, string $barangay): void
    {
        if (!class_exists('\Dompdf\Dompdf')) {
            Session::flash('error', 'Dompdf is not available.');
            $this->redirect('/reports');
        }

        $typeLabel = match($type) {
            'opt'       => 'Operation Timbang Plus (OPT)',
            'dsp'       => 'Dietary Supplementation Program (DSP)',
            'dsp_ready' => 'DSP — Ready to Discharge',
            'mns_vita'  => 'Vitamin A Distribution',
            'mns_mnp'   => 'MNP (Micronutrient Powder)',
            'mns_lns'   => 'LNS-SQ (Lipid-based Nutrient Supplement)',
            default     => strtoupper($type),
        };
        $title = $typeLabel . ' Report — ' . $year . ($period ? " ($period)" : '') . ($barangay ? " — $barangay" : '');

        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
            h2 { font-size: 12px; margin-bottom: 6px; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #2563eb; color: white; padding: 4px 6px; text-align: left; }
            td { padding: 3px 6px; border-bottom: 1px solid #e5e7eb; }
            tr:nth-child(even) td { background: #f8fafc; }
        </style></head><body>';
        $html .= '<h2>' . htmlspecialchars($title) . '</h2>';
        $html .= '<p style="font-size:8px;color:#6b7280;">Generated: ' . date('F j, Y H:i') . ' &nbsp;|&nbsp; Total: ' . count($rows) . ' records</p>';
        $html .= '<table><tr>';
        foreach ($headers as $h) {
            $html .= '<th>' . htmlspecialchars($h) . '</th>';
        }
        $html .= '</tr>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($this->mapRowForCsv($row, $type) as $cell) {
                $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table></body></html>';

        $dompdf = new \Dompdf\Dompdf(['defaultPaperSize' => 'legal', 'defaultPaperOrientation' => 'landscape']);
        $dompdf->loadHtml($html);
        $dompdf->render();
        $dompdf->stream($filename . '.pdf', ['Attachment' => true]);
        exit;
    }

    private function getReportData(string $type, int $year, string $period, string $barangay, string $source = '', string $dateFrom = '', string $dateTo = ''): array
    {
        $db = Database::getInstance();

        if ($type === 'opt') {
            if ($dateFrom && $dateTo) {
                $params = [$dateFrom, $dateTo]; $where = 'a.assessment_date BETWEEN ? AND ?';
            } else {
                $params = [$year]; $where = 'a.assessment_year = ?';
                if ($period) { $where .= ' AND a.period = ?'; $params[] = $period; }
            }
            if ($barangay) { $where .= ' AND b.barangay = ?'; $params[] = $barangay; }
            if ($source)   { $where .= ' AND b.source = ?';   $params[] = $source; }
            $stmt = $db->prepare(
                "SELECT a.*, b.last_name, b.first_name, b.middle_name, b.barangay, b.sex, b.date_of_birth
                 FROM assessments a JOIN beneficiaries b ON b.id = a.beneficiary_id
                 WHERE b.deleted_at IS NULL AND b.validation_status = 'validated' AND $where
                   AND a.id = (
                       SELECT id FROM assessments a2
                       WHERE a2.beneficiary_id = a.beneficiary_id
                         AND a2.assessment_year = a.assessment_year
                         AND a2.period = a.period
                       ORDER BY a2.assessment_date DESC LIMIT 1
                   )
                 ORDER BY b.barangay, b.last_name"
            );
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        if ($type === 'dsp') {
            if ($dateFrom && $dateTo) {
                $params = [$dateFrom, $dateTo]; $where = 'pe.enrollment_date BETWEEN ? AND ?';
            } else {
                $params = [$year]; $where = 'pe.cycle_year = ?';
            }
            if ($barangay) { $where .= ' AND b.barangay = ?'; $params[] = $barangay; }
            if ($source)   { $where .= ' AND b.source = ?';   $params[] = $source; }
            $stmt = $db->prepare(
                "SELECT pe.*, b.last_name, b.first_name, b.barangay, b.date_of_birth
                 FROM program_enrollments pe JOIN beneficiaries b ON b.id = pe.beneficiary_id
                 WHERE pe.program = 'DSP' AND b.validation_status = 'validated' AND $where ORDER BY b.barangay, b.last_name"
            );
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        if ($type === 'dsp_ready') {
            $params = []; $where = '';
            if ($barangay) { $where = ' AND b.barangay = ?'; $params[] = $barangay; }
            $stmt = $db->prepare(
                "SELECT pe.*,
                        (SELECT COUNT(*) FROM program_enrollments pe2
                         WHERE pe2.beneficiary_id = pe.beneficiary_id AND pe2.program = 'DSP' AND pe2.id <= pe.id) AS cycle_number,
                        b.last_name, b.first_name, b.barangay, b.date_of_birth,
                        a.nutritional_status, a.wflh_status, a.assessment_date, a.weight_kg
                 FROM program_enrollments pe
                 JOIN beneficiaries b ON b.id = pe.beneficiary_id
                 JOIN assessments a ON a.id = (
                     SELECT id FROM assessments WHERE beneficiary_id = pe.beneficiary_id
                     ORDER BY assessment_date DESC LIMIT 1
                 )
                 WHERE pe.program = 'DSP' AND pe.status = 'Active'
                 AND a.nutritional_status NOT IN ('SUW', 'UW')
                 AND (a.wflh_status IS NULL OR a.wflh_status NOT IN ('SW', 'MW'))
                 $where
                 ORDER BY b.barangay, b.last_name"
            );
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        if ($type === 'mns_vita') {
            if ($dateFrom && $dateTo) {
                $params = [$dateFrom, $dateTo]; $where = 'v.distribution_date BETWEEN ? AND ?';
            } else {
                $params = [$year]; $where = 'v.year = ?';
                if ($period) { $where .= ' AND v.round = ?'; $params[] = $period; }
            }
            if ($barangay) { $where .= ' AND b.barangay = ?'; $params[] = $barangay; }
            if ($source)   { $where .= ' AND b.source = ?';   $params[] = $source; }
            $stmt = $db->prepare(
                "SELECT v.*, b.last_name, b.first_name, b.barangay, b.date_of_birth
                 FROM vitamin_a_records v JOIN beneficiaries b ON b.id = v.beneficiary_id
                 WHERE b.validation_status = 'validated' AND $where ORDER BY b.barangay, b.last_name"
            );
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        if ($type === 'mns_mnp') {
            if ($dateFrom && $dateTo) {
                $params = [$dateFrom, $dateTo]; $where = 'm.date_given BETWEEN ? AND ?';
            } else {
                $params = [$year]; $where = 'm.year = ?';
            }
            if ($barangay) { $where .= ' AND b.barangay = ?'; $params[] = $barangay; }
            if ($source)   { $where .= ' AND b.source = ?';   $params[] = $source; }
            $stmt = $db->prepare(
                "SELECT m.*, b.last_name, b.first_name, b.barangay, b.date_of_birth
                 FROM mnp_records m JOIN beneficiaries b ON b.id = m.beneficiary_id
                 WHERE b.validation_status = 'validated' AND $where ORDER BY b.barangay, b.last_name"
            );
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        if ($type === 'mns_lns') {
            if ($dateFrom && $dateTo) {
                $params = [$dateFrom, $dateTo]; $where = 'l.date_given BETWEEN ? AND ?';
            } else {
                $params = [$year]; $where = 'l.year = ?';
            }
            if ($barangay) { $where .= ' AND b.barangay = ?'; $params[] = $barangay; }
            if ($source)   { $where .= ' AND b.source = ?';   $params[] = $source; }
            $stmt = $db->prepare(
                "SELECT l.*, b.last_name, b.first_name, b.barangay, b.date_of_birth
                 FROM lns_sq_records l JOIN beneficiaries b ON b.id = l.beneficiary_id
                 WHERE b.validation_status = 'validated' AND $where ORDER BY b.barangay, b.last_name"
            );
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        return [];
    }

    private function getCsvHeaders(string $type): array
    {
        return match($type) {
            'opt' => ['Barangay','Last Name','First Name','DOB','Sex','Age (mo)','Weight (kg)','Height (cm)','WFA Z-Score','WFA Status','HFA Z-Score','HFA Status','WFL/H Z-Score','WFL/H Status','MUAC (cm)','Period','Year'],
            'dsp' => ['Barangay','Last Name','First Name','DOB','Enrollment Date','Intervention Type','Pre-Weight (kg)','Post-Weight (kg)','Weight Gain (kg)','Status','End Date','Year'],
            'dsp_ready' => ['Barangay','Last Name','First Name','DOB','Cycle','Intervention','Enrollment Date','WFA Status','WFL/H Status','Last Assessment Date'],
            'mns_vita' => ['Barangay','Last Name','First Name','DOB','Distribution Date','Round','Year','Dosage (IU)','Capsule Color','Administered By'],
            'mns_mnp'  => ['Barangay','Last Name','First Name','DOB','Date Given','Year','Age Group','Routine Completed'],
            'mns_lns'  => ['Barangay','Last Name','First Name','DOB','Date Given','Year','Age Group','Routine Completed'],
            default => [],
        };
    }

    private function mapRowForCsv(array $row, string $type): array
    {
        return match($type) {
            'opt' => [$row['barangay'],$row['last_name'],$row['first_name'],$this->formatDate($row['date_of_birth']),$row['sex'],$row['age_in_months'],$row['weight_kg'],$row['height_cm']??'',$row['weight_for_age_zscore']??'',$row['nutritional_status'],$row['height_for_age_zscore']??'',$row['hfa_status']??'',$row['wflh_zscore']??'',$row['wflh_status']??'',$row['muac_cm']??'',$row['period'],$row['assessment_year']],
            'dsp' => [
                $row['barangay'], $row['last_name'], $row['first_name'],
                $this->formatDate($row['date_of_birth']),
                $this->formatDate($row['enrollment_date']),
                $row['intervention_type'] ?? '',
                $row['pre_weight_kg'] ?? '',
                $row['post_weight_kg'] ?? '',
                (isset($row['pre_weight_kg'], $row['post_weight_kg']) && $row['pre_weight_kg'] > 0 && $row['post_weight_kg'] > 0)
                    ? round($row['post_weight_kg'] - $row['pre_weight_kg'], 2) : '',
                $row['status'],
                $this->formatDate($row['end_date'] ?? ''),
                $row['cycle_year'],
            ],
            'dsp_ready' => [
                $row['barangay'], $row['last_name'], $row['first_name'],
                $this->formatDate($row['date_of_birth']),
                'Cycle ' . $row['cycle_number'],
                $row['intervention_type'] ?? '',
                $this->formatDate($row['enrollment_date']),
                $row['nutritional_status'],
                $row['wflh_status'] ?? 'Normal',
                $this->formatDate($row['assessment_date']),
            ],
            'mns_vita' => [$row['barangay'],$row['last_name'],$row['first_name'],$this->formatDate($row['date_of_birth']),$this->formatDate($row['distribution_date']),$row['round'],$row['year'],$row['dosage_iu'],$row['capsule_color'],$row['administered_by']??''],
            'mns_mnp'  => [$row['barangay'],$row['last_name'],$row['first_name'],$this->formatDate($row['date_of_birth']),$this->formatDate($row['date_given']),$row['year'],$row['age_group'],$row['completed_routine'] ? 'Yes' : 'No'],
            'mns_lns'  => [$row['barangay'],$row['last_name'],$row['first_name'],$this->formatDate($row['date_of_birth']),$this->formatDate($row['date_given']),$row['year'],$row['age_group'],$row['completed_routine'] ? 'Yes' : 'No'],
            default => [],
        };
    }

    private function exportMonthlyExcel(int $year, string $barangay, int $month = 0): void
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            Session::flash('error', 'PhpSpreadsheet is not available.');
            $this->redirect('/reports');
        }

        $db         = Database::getInstance();
        $monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
        $monthLabel = $month > 0 ? $monthNames[$month] . ' ' . $year : (string)$year;
        $monthPad   = $month > 0 ? str_pad($month, 2, '0', STR_PAD_LEFT) : null;

        $mWhere = 'm.year = ?'; $mParams = [$year];
        $lWhere = 'l.year = ?'; $lParams = [$year];
        if ($monthPad) {
            $mWhere .= " AND MONTH(m.date_given) = ?"; $mParams[] = $month;
            $lWhere .= " AND MONTH(l.date_given) = ?"; $lParams[] = $month;
        }
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
        $mnpRecords = $stmt->fetchAll();

        $stmt = $db->prepare(
            "SELECT l.*, b.last_name, b.first_name, b.barangay, b.date_of_birth
             FROM lns_sq_records l JOIN beneficiaries b ON b.id = l.beneficiary_id
             WHERE $lWhere ORDER BY l.date_given, b.last_name"
        );
        $stmt->execute($lParams);
        $lnsRecords = $stmt->fetchAll();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $white = 'FFFFFF'; $gray = 'F0F4FF';

        $styleHeader = function($sheet, string $range, string $bgColor) use ($white) {
            $style = $sheet->getStyle($range);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setRGB($white);
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($bgColor);
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB($white);
        };

        $writeRows = function($sheet, array $records) use ($gray, $white) {
            foreach ($records as $idx => $r) {
                $row = $idx + 2;
                $sheet->setCellValue('A' . $row, $r['barangay']);
                $sheet->setCellValue('B' . $row, $r['last_name']);
                $sheet->setCellValue('C' . $row, $r['first_name']);
                $sheet->setCellValue('D' . $row, $this->formatDate($r['date_of_birth']));
                $sheet->setCellValue('E' . $row, $this->formatDate($r['date_given']));
                $sheet->setCellValue('F' . $row, $r['age_group']);
                $sheet->setCellValue('G' . $row, $r['completed_routine'] ? 'Yes' : 'No');
                $bg = $idx % 2 === 0 ? $gray : $white;
                $sheet->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
                $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
                $sheet->getRowDimension($row)->setRowHeight(16);
            }
            foreach (range(1, 7) as $col) $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            $sheet->setAutoFilter('A1:G1');
            $sheet->freezePane('A2');
        };

        $detailHeaders = ['Barangay', 'Last Name', 'First Name', 'DOB', 'Date Given', 'Age Group', 'Completed'];

        // ── Sheet 1: MNP ──
        $sheet1 = $spreadsheet->getActiveSheet()->setTitle('MNP');
        foreach ($detailHeaders as $i => $h) {
            $sheet1->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . '1', $h);
        }
        $styleHeader($sheet1, 'A1:G1', 'D97706');
        $sheet1->getRowDimension(1)->setRowHeight(20);
        $writeRows($sheet1, $mnpRecords);

        // ── Sheet 2: LNS-SQ ──
        $sheet2 = $spreadsheet->createSheet()->setTitle('LNS-SQ');
        foreach ($detailHeaders as $i => $h) {
            $sheet2->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . '1', $h);
        }
        $styleHeader($sheet2, 'A1:G1', '166534');
        $sheet2->getRowDimension(1)->setRowHeight(20);
        $writeRows($sheet2, $lnsRecords);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'MNS_' . str_replace(' ', '_', $monthLabel) . ($barangay ? '_' . preg_replace('/[^A-Za-z0-9]/', '_', $barangay) : '');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}.xlsx\"");
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }
}
