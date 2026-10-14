<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;
use App\Models\Beneficiary;

class ReportApiController extends ApiController
{
    public function summary(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $db   = Database::getInstance();

        $year   = (int)($_GET['year']   ?? date('Y'));
        $period = $_GET['period']        ?? '';
        $role   = strtolower($user['role'] ?? '');
        $brgy   = in_array($role, ['bhw','encoder']) ? ($user['barangay'] ?? null) : ($_GET['barangay'] ?? null);

        $bnsFilter = Beneficiary::BNS_SUBMITTED_FILTER;
        $bWhere  = ['b.deleted_at IS NULL', "b.validation_status = 'validated'", $bnsFilter];
        $aWhere  = ['b.deleted_at IS NULL', "b.validation_status = 'validated'", $bnsFilter, "a.validation_status = 'validated'", 'a.assessment_year = ?'];
        $aParams = [$year];
        $bParams = [];

        if ($brgy) {
            $bWhere[] = 'b.barangay = ?'; $bParams[] = $brgy;
            $aWhere[] = 'b.barangay = ?'; $aParams[] = $brgy;
        }
        if ($period) { $aWhere[] = 'a.period = ?'; $aParams[] = $period; }

        // Total beneficiaries
        $stmt = $db->prepare("SELECT COUNT(*) FROM beneficiaries b WHERE " . implode(' AND ', $bWhere));
        $stmt->execute($bParams);
        $total = (int)$stmt->fetchColumn();

        // Nutritional status counts
        $stmt = $db->prepare(
            "SELECT a.nutritional_status, COUNT(*) as cnt
             FROM assessments a JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE " . implode(' AND ', $aWhere) . "
             GROUP BY a.nutritional_status"
        );
        $stmt->execute($aParams);
        $statusCounts = [];
        foreach ($stmt->fetchAll() as $r) {
            $statusCounts[$r['nutritional_status']] = (int)$r['cnt'];
        }

        // Total assessed
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT a.beneficiary_id) FROM assessments a
             JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE " . implode(' AND ', $aWhere)
        );
        $stmt->execute($aParams);
        $totalAssessed = (int)$stmt->fetchColumn();

        // By barangay
        $brgWhere  = ['b.deleted_at IS NULL', "b.validation_status = 'validated'", $bnsFilter, "a.validation_status = 'validated'", 'a.assessment_year = ?'];
        $brgParams = [$year];
        if ($period) { $brgWhere[] = 'a.period = ?'; $brgParams[] = $period; }
        if ($brgy)   { $brgWhere[] = 'b.barangay = ?'; $brgParams[] = $brgy; }

        $stmt = $db->prepare(
            "SELECT b.barangay,
                    COUNT(*) as total,
                    SUM(CASE WHEN a.nutritional_status='SUW' THEN 1 ELSE 0 END) as suw,
                    SUM(CASE WHEN a.nutritional_status='UW'  THEN 1 ELSE 0 END) as uw,
                    SUM(CASE WHEN a.nutritional_status='OW'  THEN 1 ELSE 0 END) as ow,
                    SUM(CASE WHEN a.nutritional_status='OB'  THEN 1 ELSE 0 END) as ob,
                    SUM(CASE WHEN a.nutritional_status='Normal' THEN 1 ELSE 0 END) as normal
             FROM assessments a
             JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE " . implode(' AND ', $brgWhere) . "
             GROUP BY b.barangay
             ORDER BY b.barangay"
        );
        $stmt->execute($brgParams);
        $byBarangay = $stmt->fetchAll();

        $this->success([
            'year'            => $year,
            'period'          => $period,
            'total_bene'      => $total,
            'total_assessed'  => $totalAssessed,
            'status_counts'   => $statusCounts,
            'by_barangay'     => $byBarangay,
        ]);
    }

    public function opt(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $db   = Database::getInstance();
        $year = (int)($_GET['year'] ?? date('Y'));
        $period = $_GET['period'] ?? '';
        $role = strtolower($user['role'] ?? '');
        $brgy = in_array($role,['bhw','encoder']) ? ($user['barangay']??null) : ($_GET['barangay']??null);

        $where = ['b.deleted_at IS NULL', "b.validation_status='validated'", "a.validation_status='validated'", 'a.assessment_year=?']; $params = [$year];
        if ($period) { $where[] = 'a.period=?'; $params[] = $period; }
        if ($brgy)   { $where[] = 'b.barangay=?'; $params[] = $brgy; }

        $stmt = $db->prepare("SELECT a.*,b.last_name,b.first_name,b.barangay,b.sex,b.date_of_birth FROM assessments a JOIN beneficiaries b ON b.id=a.beneficiary_id WHERE ".implode(' AND ',$where)." ORDER BY b.barangay,b.last_name");
        $stmt->execute($params); $rows = $stmt->fetchAll();

        $counts = ['Normal'=>0,'UW'=>0,'SUW'=>0,'OW'=>0,'OB'=>0];
        $bySex = ['Male'=>['Normal'=>0,'UW'=>0,'SUW'=>0,'OW'=>0,'OB'=>0],'Female'=>['Normal'=>0,'UW'=>0,'SUW'=>0,'OW'=>0,'OB'=>0]];
        foreach ($rows as $r) {
            $s = $r['nutritional_status'] ?? 'Normal'; $sex = $r['sex'] ?? 'Male';
            if (isset($counts[$s])) $counts[$s]++;
            if (isset($bySex[$sex][$s])) $bySex[$sex][$s]++;
        }
        $this->success(['year'=>$year,'period'=>$period,'assessments'=>$rows,'counts'=>$counts,'by_sex'=>$bySex,'total'=>count($rows)]);
    }

    public function dsp(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $db   = Database::getInstance();
        $year = (int)($_GET['year'] ?? date('Y'));
        $role = strtolower($user['role'] ?? '');
        $brgy = in_array($role,['bhw','encoder','bns']) ? ($user['barangay']??null) : ($_GET['barangay']??null);

        $where = ["pe.program='DSP'","pe.cycle_year=?","b.deleted_at IS NULL","b.validation_status='validated'"]; $params = [$year];
        if ($brgy) { $where[] = 'b.barangay=?'; $params[] = $brgy; }
        $w = implode(' AND ', $where);

        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN pe.status='Active' THEN 1 ELSE 0 END) as active, SUM(CASE WHEN pe.status='Completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN pe.status='Dropped' THEN 1 ELSE 0 END) as dropped FROM program_enrollments pe JOIN beneficiaries b ON b.id=pe.beneficiary_id WHERE $w");
        $stmt->execute($params); $totals = $stmt->fetch();

        // By barangay structured
        $stmt = $db->prepare("SELECT b.barangay, pe.status, COUNT(*) as cnt FROM program_enrollments pe JOIN beneficiaries b ON b.id=pe.beneficiary_id WHERE $w GROUP BY b.barangay, pe.status ORDER BY b.barangay");
        $stmt->execute($params); $rawBrgy = [];
        foreach ($stmt->fetchAll() as $r) $rawBrgy[$r['barangay']][$r['status']] = (int)$r['cnt'];
        $byBarangay = [];
        foreach ($rawBrgy as $bar => $s) {
            $byBarangay[] = ['barangay'=>$bar, 'active'=>$s['Active']??0, 'completed'=>$s['Completed']??0, 'dropped'=>$s['Dropped']??0, 'total'=>array_sum($s)];
        }

        // By intervention type
        $stmt = $db->prepare("SELECT COALESCE(pe.intervention_type,'Unknown') as intervention_type, COUNT(*) as cnt FROM program_enrollments pe JOIN beneficiaries b ON b.id=pe.beneficiary_id WHERE $w GROUP BY pe.intervention_type");
        $stmt->execute($params); $byIntervention = [];
        foreach ($stmt->fetchAll() as $r) $byIntervention[$r['intervention_type']] = (int)$r['cnt'];

        // Weight gain for completed enrollments
        $stmt = $db->prepare("SELECT AVG(pe.post_weight_kg - pe.pre_weight_kg) as avg_gain, COUNT(*) as cnt FROM program_enrollments pe JOIN beneficiaries b ON b.id=pe.beneficiary_id WHERE $w AND pe.status='Completed' AND pe.pre_weight_kg IS NOT NULL AND pe.post_weight_kg IS NOT NULL");
        $stmt->execute($params); $weightGain = $stmt->fetch();

        $this->success([
            'year'            => $year,
            'totals'          => $totals,
            'by_barangay'     => $byBarangay,
            'by_intervention' => $byIntervention,
            'avg_weight_gain' => $weightGain['cnt'] > 0 ? round($weightGain['avg_gain'], 2) : null,
            'completed_count' => (int)($weightGain['cnt'] ?? 0),
        ]);
    }

    public function mns(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $db   = Database::getInstance();
        $year = (int)($_GET['year'] ?? date('Y'));
        $role = strtolower($user['role'] ?? '');
        $brgy = in_array($role,['bhw','encoder','bns']) ? ($user['barangay']??null) : ($_GET['barangay']??null);

        $bWhere = ['b.deleted_at IS NULL', "b.validation_status='validated'"]; $bParams = [];
        if ($brgy) { $bWhere[] = 'b.barangay=?'; $bParams[] = $brgy; }

        $vaWhere = array_merge(['v.year=?'], $bWhere); $vaParams = array_merge([$year], $bParams);

        // Vitamin A totals
        $stmt = $db->prepare("SELECT COUNT(*) FROM vitamin_a_records v JOIN beneficiaries b ON b.id=v.beneficiary_id WHERE ".implode(' AND ',$vaWhere));
        $stmt->execute($vaParams); $vitaminACount = (int)$stmt->fetchColumn();

        // Vitamin A by round
        $stmt = $db->prepare("SELECT v.round, COUNT(*) as cnt FROM vitamin_a_records v JOIN beneficiaries b ON b.id=v.beneficiary_id WHERE ".implode(' AND ',$vaWhere)." GROUP BY v.round");
        $stmt->execute($vaParams); $vitaByRound = [];
        foreach ($stmt->fetchAll() as $r) $vitaByRound[$r['round']] = (int)$r['cnt'];

        // Vitamin A by barangay + round
        $stmt = $db->prepare("SELECT b.barangay, v.round, COUNT(*) as cnt FROM vitamin_a_records v JOIN beneficiaries b ON b.id=v.beneficiary_id WHERE ".implode(' AND ',$vaWhere)." GROUP BY b.barangay, v.round ORDER BY b.barangay");
        $stmt->execute($vaParams); $vitaRaw = [];
        foreach ($stmt->fetchAll() as $r) $vitaRaw[$r['barangay']][$r['round']] = (int)$r['cnt'];
        $vitaByBarangay = [];
        foreach ($vitaRaw as $bar => $rounds) {
            $vitaByBarangay[] = ['barangay'=>$bar, 'february'=>$rounds['February']??0, 'august'=>$rounds['August']??0, 'total'=>array_sum($rounds)];
        }

        // MNP
        $mnpWhere = array_merge(['m.year=?'], $bWhere); $mnpParams = array_merge([$year], $bParams);
        $stmt = $db->prepare("SELECT COUNT(*) FROM mnp_records m JOIN beneficiaries b ON b.id=m.beneficiary_id WHERE ".implode(' AND ',$mnpWhere));
        $stmt->execute($mnpParams); $mnpCount = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT b.barangay, COUNT(*) as cnt FROM mnp_records m JOIN beneficiaries b ON b.id=m.beneficiary_id WHERE ".implode(' AND ',$mnpWhere)." GROUP BY b.barangay ORDER BY b.barangay");
        $stmt->execute($mnpParams); $mnpByBarangay = $stmt->fetchAll();

        // LNS-SQ
        $lnsWhere = array_merge(['l.year=?'], $bWhere); $lnsParams = array_merge([$year], $bParams);
        $stmt = $db->prepare("SELECT COUNT(*) FROM lns_sq_records l JOIN beneficiaries b ON b.id=l.beneficiary_id WHERE ".implode(' AND ',$lnsWhere));
        $stmt->execute($lnsParams); $lnsCount = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT b.barangay, COUNT(*) as cnt FROM lns_sq_records l JOIN beneficiaries b ON b.id=l.beneficiary_id WHERE ".implode(' AND ',$lnsWhere)." GROUP BY b.barangay ORDER BY b.barangay");
        $stmt->execute($lnsParams); $lnsByBarangay = $stmt->fetchAll();

        $this->success([
            'year'             => $year,
            'vitamin_a'        => $vitaminACount,
            'mnp'              => $mnpCount,
            'lns_sq'           => $lnsCount,
            'vita_by_round'    => $vitaByRound,
            'vita_by_barangay' => $vitaByBarangay,
            'mnp_by_barangay'  => $mnpByBarangay,
            'lns_by_barangay'  => $lnsByBarangay,
        ]);
    }

    public function outcome(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $db   = Database::getInstance();
        $year = (int)($_GET['year'] ?? date('Y'));
        $role = strtolower($user['role'] ?? '');
        $brgy = in_array($role,['bhw','encoder']) ? ($user['barangay']??null) : ($_GET['barangay']??null);

        $where = ['b.deleted_at IS NULL', "b.validation_status='validated'", "a.validation_status='validated'", 'a.assessment_year=?']; $params = [$year];
        if ($brgy) { $where[] = 'b.barangay=?'; $params[] = $brgy; }

        $stmt = $db->prepare("SELECT a.period, a.nutritional_status, COUNT(*) as cnt FROM assessments a JOIN beneficiaries b ON b.id=a.beneficiary_id WHERE ".implode(' AND ',$where)." GROUP BY a.period, a.nutritional_status");
        $stmt->execute($params);
        $byPeriod = [];
        foreach ($stmt->fetchAll() as $r) $byPeriod[$r['period']][$r['nutritional_status']] = (int)$r['cnt'];

        $this->success(['year'=>$year,'by_period'=>$byPeriod]);
    }

    public function comparison(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $db   = Database::getInstance();
        $role = strtolower($user['role'] ?? '');
        $brgy = in_array($role,['bhw','encoder']) ? ($user['barangay']??null) : ($_GET['barangay']??null);
        $year1 = (int)($_GET['year1'] ?? (date('Y') - 1));
        $year2 = (int)($_GET['year2'] ?? date('Y'));

        $result = [];
        foreach ([$year1, $year2] as $yr) {
            $where = ['b.deleted_at IS NULL', "b.validation_status='validated'", "a.validation_status='validated'", 'a.assessment_year=?']; $params = [$yr];
            if ($brgy) { $where[] = 'b.barangay=?'; $params[] = $brgy; }
            $stmt = $db->prepare("SELECT a.nutritional_status, COUNT(*) as cnt FROM assessments a JOIN beneficiaries b ON b.id=a.beneficiary_id WHERE ".implode(' AND ',$where)." GROUP BY a.nutritional_status");
            $stmt->execute($params);
            $result[$yr] = [];
            foreach ($stmt->fetchAll() as $r) $result[$yr][$r['nutritional_status']] = (int)$r['cnt'];
        }
        $this->success(['year1'=>$year1,'year2'=>$year2,'data'=>$result]);
    }

    public function export(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $db     = Database::getInstance();
        $type   = $_GET['type']   ?? 'opt';
        $format = $_GET['format'] ?? 'csv'; // csv | excel
        $year   = (int)($_GET['year'] ?? date('Y'));
        $period = $_GET['period'] ?? '';
        $role   = strtolower($user['role'] ?? '');
        $brgy   = in_array($role, ['bhw','bns']) ? ($user['barangay'] ?? null) : ($_GET['barangay'] ?? null);

        $bnsFilter = \App\Models\Beneficiary::BNS_SUBMITTED_FILTER;
        [$rows, $headers, $filename] = $this->buildExportData($db, $type, $year, $period, $brgy, $bnsFilter);

        if ($format === 'excel' && class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $this->streamExcel($rows, $headers, $type, $filename);
            return;
        }

        // Default: CSV
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        header('Cache-Control: no-cache, no-store, must-revalidate');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
        fputcsv($out, $headers);
        foreach ($rows as $row) fputcsv($out, $this->mapExportRow($row, $type));
        fclose($out);
        exit;
    }

    private function buildExportData(\Core\Database $db, string $type, int $year, string $period, ?string $brgy, string $bnsFilter): array
    {
        $fmt = fn($d) => $d ? date('Y-m-d', strtotime($d)) : '';

        if ($type === 'opt') {
            $where = ["b.deleted_at IS NULL","b.validation_status='validated'",$bnsFilter,"a.validation_status='validated'","a.assessment_year=?"]; $params = [$year];
            if ($period) { $where[] = "a.period=?"; $params[] = $period; }
            if ($brgy)   { $where[] = "b.barangay=?"; $params[] = $brgy; }
            $stmt = $db->prepare("SELECT a.*,b.last_name,b.first_name,b.barangay,b.sex,b.date_of_birth FROM assessments a JOIN beneficiaries b ON b.id=a.beneficiary_id WHERE ".implode(' AND ',$where)." ORDER BY b.barangay,b.last_name");
            $stmt->execute($params);
            return [$stmt->fetchAll(), ['Barangay','Last Name','First Name','DOB','Sex','Age (mo)','Weight (kg)','Height (cm)','WFA Z-Score','WFA Status','WFL/H Z-Score','WFL/H Status','MUAC (cm)','Period','Year'], "OPT_OperationTimbang_{$year}"];
        }

        if ($type === 'dsp') {
            $where = ["pe.program='DSP'","b.deleted_at IS NULL","b.validation_status='validated'",$bnsFilter,"pe.cycle_year=?"]; $params = [$year];
            if ($brgy) { $where[] = "b.barangay=?"; $params[] = $brgy; }
            $stmt = $db->prepare("SELECT pe.*,b.last_name,b.first_name,b.barangay,b.date_of_birth FROM program_enrollments pe JOIN beneficiaries b ON b.id=pe.beneficiary_id WHERE ".implode(' AND ',$where)." ORDER BY b.barangay,b.last_name");
            $stmt->execute($params);
            return [$stmt->fetchAll(), ['Barangay','Last Name','First Name','DOB','Enrollment Date','Intervention','Pre-Weight (kg)','Post-Weight (kg)','Weight Gain (kg)','Status','End Date','Year'], "DSP_Enrollments_{$year}"];
        }

        if ($type === 'mns_vita') {
            $where = ["b.deleted_at IS NULL","b.validation_status='validated'",$bnsFilter,"v.year=?"]; $params = [$year];
            if ($period) { $where[] = "v.round=?"; $params[] = $period; }
            if ($brgy)   { $where[] = "b.barangay=?"; $params[] = $brgy; }
            $stmt = $db->prepare("SELECT v.*,b.last_name,b.first_name,b.barangay,b.date_of_birth FROM vitamin_a_records v JOIN beneficiaries b ON b.id=v.beneficiary_id WHERE ".implode(' AND ',$where)." ORDER BY b.barangay,b.last_name");
            $stmt->execute($params);
            return [$stmt->fetchAll(), ['Barangay','Last Name','First Name','DOB','Distribution Date','Round','Year','Dosage (IU)','Capsule Color','Administered By'], "MNS_VitaminA_{$year}"];
        }

        if ($type === 'mns_mnp') {
            $where = ["b.deleted_at IS NULL","b.validation_status='validated'",$bnsFilter,"m.year=?"]; $params = [$year];
            if ($brgy) { $where[] = "b.barangay=?"; $params[] = $brgy; }
            $stmt = $db->prepare("SELECT m.*,b.last_name,b.first_name,b.barangay,b.date_of_birth FROM mnp_records m JOIN beneficiaries b ON b.id=m.beneficiary_id WHERE ".implode(' AND ',$where)." ORDER BY b.barangay,b.last_name");
            $stmt->execute($params);
            return [$stmt->fetchAll(), ['Barangay','Last Name','First Name','DOB','Date Given','Year','Age Group','Routine Completed'], "MNS_MNP_{$year}"];
        }

        if ($type === 'mns_lns') {
            $where = ["b.deleted_at IS NULL","b.validation_status='validated'",$bnsFilter,"l.year=?"]; $params = [$year];
            if ($brgy) { $where[] = "b.barangay=?"; $params[] = $brgy; }
            $stmt = $db->prepare("SELECT l.*,b.last_name,b.first_name,b.barangay,b.date_of_birth FROM lns_sq_records l JOIN beneficiaries b ON b.id=l.beneficiary_id WHERE ".implode(' AND ',$where)." ORDER BY b.barangay,b.last_name");
            $stmt->execute($params);
            return [$stmt->fetchAll(), ['Barangay','Last Name','First Name','DOB','Date Given','Year','Age Group','Routine Completed'], "MNS_LNSSQ_{$year}"];
        }

        return [[], [], "report_{$year}"];
    }

    private function mapExportRow(array $row, string $type): array
    {
        $fmt = fn($d) => $d ? date('Y-m-d', strtotime($d)) : '';
        return match($type) {
            'opt'      => [$row['barangay'],$row['last_name'],$row['first_name'],$fmt($row['date_of_birth']),$row['sex'],$row['age_in_months']??'',$row['weight_kg'],$row['height_cm']??'',$row['weight_for_age_zscore']??'',$row['nutritional_status'],$row['wflh_zscore']??'',$row['wflh_status']??'',$row['muac_cm']??'',$row['period'],$row['assessment_year']],
            'dsp'      => [$row['barangay'],$row['last_name'],$row['first_name'],$fmt($row['date_of_birth']),$fmt($row['enrollment_date']),$row['intervention_type']??'',$row['pre_weight_kg']??'',$row['post_weight_kg']??'',(isset($row['pre_weight_kg'],$row['post_weight_kg'])&&$row['pre_weight_kg']>0&&$row['post_weight_kg']>0)?round($row['post_weight_kg']-$row['pre_weight_kg'],2):'',$row['status'],$fmt($row['end_date']??''),$row['cycle_year']],
            'mns_vita' => [$row['barangay'],$row['last_name'],$row['first_name'],$fmt($row['date_of_birth']),$fmt($row['distribution_date']),$row['round'],$row['year'],$row['dosage_iu']??'',$row['capsule_color']??'',$row['administered_by']??''],
            'mns_mnp'  => [$row['barangay'],$row['last_name'],$row['first_name'],$fmt($row['date_of_birth']),$fmt($row['date_given']),$row['year'],$row['age_group']??'',$row['completed_routine']?'Yes':'No'],
            'mns_lns'  => [$row['barangay'],$row['last_name'],$row['first_name'],$fmt($row['date_of_birth']),$fmt($row['date_given']),$row['year'],$row['age_group']??'',$row['completed_routine']?'Yes':'No'],
            default    => array_values($row),
        };
    }

    private function streamExcel(array $rows, array $headers, string $type, string $filename): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(strtoupper(str_replace('_','',$type)));
        $colCount = count($headers);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        foreach ($headers as $i => $h) {
            $col  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $cell = $col . '1';
            $sheet->setCellValue($cell, $h);
            $style = $sheet->getStyle($cell);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E40AF');
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getRowDimension(1)->setRowHeight(24);

        foreach ($rows as $ri => $row) {
            $data = $this->mapExportRow($row, $type);
            foreach ($data as $ci => $val) {
                $col  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
                $cell = $col . ($ri + 2);
                $sheet->setCellValue($cell, $val);
                $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($ri % 2 === 0 ? 'F0F4FF' : 'FFFFFF');
            }
        }
        foreach (range(1, $colCount) as $c) $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        $sheet->setAutoFilter("A1:{$lastCol}1");
        $sheet->freezePane('A2');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}.xlsx\"");
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    public function distribution(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $db   = Database::getInstance();
        $year = (int)($_GET['year'] ?? date('Y'));
        $period = $_GET['period'] ?? '';
        $role = strtolower($user['role'] ?? '');
        $brgy = in_array($role,['bhw','encoder']) ? ($user['barangay']??null) : ($_GET['barangay']??null);

        $where = ['b.deleted_at IS NULL', "b.validation_status='validated'", "a.validation_status='validated'", 'a.assessment_year=?']; $params = [$year];
        if ($period) { $where[] = 'a.period=?'; $params[] = $period; }
        if ($brgy)   { $where[] = 'b.barangay=?'; $params[] = $brgy; }

        $stmt = $db->prepare("SELECT b.barangay, a.nutritional_status, COUNT(*) as cnt FROM assessments a JOIN beneficiaries b ON b.id=a.beneficiary_id WHERE ".implode(' AND ',$where)." GROUP BY b.barangay, a.nutritional_status ORDER BY b.barangay");
        $stmt->execute($params);
        $byBarangay = [];
        foreach ($stmt->fetchAll() as $r) $byBarangay[$r['barangay']][$r['nutritional_status']] = (int)$r['cnt'];

        $this->success(['year'=>$year,'period'=>$period,'by_barangay'=>$byBarangay]);
    }
}
