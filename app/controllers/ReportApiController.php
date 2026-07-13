<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;

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

        $bWhere  = ['b.deleted_at IS NULL'];
        $aWhere  = ['b.deleted_at IS NULL', 'a.assessment_year = ?'];
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
        $brgWhere  = ['b.deleted_at IS NULL', 'a.assessment_year = ?'];
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

        $where = ['b.deleted_at IS NULL','a.assessment_year=?']; $params = [$year];
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
        $brgy = in_array($role,['bhw','encoder']) ? ($user['barangay']??null) : ($_GET['barangay']??null);

        $where = ["pe.program='DSP'","pe.cycle_year=?","b.deleted_at IS NULL"]; $params = [$year];
        if ($brgy) { $where[] = 'b.barangay=?'; $params[] = $brgy; }

        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN pe.status='Active' THEN 1 ELSE 0 END) as active, SUM(CASE WHEN pe.status='Completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN pe.status='Dropped' THEN 1 ELSE 0 END) as dropped FROM program_enrollments pe JOIN beneficiaries b ON b.id=pe.beneficiary_id WHERE ".implode(' AND ',$where));
        $stmt->execute($params); $totals = $stmt->fetch();

        $stmt2 = $db->prepare("SELECT b.barangay, pe.status, COUNT(*) as cnt FROM program_enrollments pe JOIN beneficiaries b ON b.id=pe.beneficiary_id WHERE ".implode(' AND ',$where)." GROUP BY b.barangay, pe.status ORDER BY b.barangay");
        $stmt2->execute($params); $byBarangay = $stmt2->fetchAll();

        $this->success(['year'=>$year,'totals'=>$totals,'by_barangay'=>$byBarangay]);
    }

    public function mns(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $db   = Database::getInstance();
        $year = (int)($_GET['year'] ?? date('Y'));
        $role = strtolower($user['role'] ?? '');
        $brgy = in_array($role,['bhw','encoder']) ? ($user['barangay']??null) : ($_GET['barangay']??null);

        $bWhere = ['b.deleted_at IS NULL']; $bParams = [];
        if ($brgy) { $bWhere[] = 'b.barangay=?'; $bParams[] = $brgy; }

        $vaWhere = array_merge(['v.year=?'], $bWhere); $vaParams = array_merge([$year], $bParams);
        $stmt = $db->prepare("SELECT COUNT(*) FROM vitamin_a_records v JOIN beneficiaries b ON b.id=v.beneficiary_id WHERE ".implode(' AND ',$vaWhere));
        $stmt->execute($vaParams); $vitaminACount = (int)$stmt->fetchColumn();

        $mnpWhere = array_merge(['m.year=?'], $bWhere); $mnpParams = array_merge([$year], $bParams);
        $stmt = $db->prepare("SELECT COUNT(*) FROM mnp_records m JOIN beneficiaries b ON b.id=m.beneficiary_id WHERE ".implode(' AND ',$mnpWhere));
        $stmt->execute($mnpParams); $mnpCount = (int)$stmt->fetchColumn();

        $lnsParams = array_merge([$year], $bParams);
        $stmt = $db->prepare("SELECT COUNT(*) FROM lns_sq_records l JOIN beneficiaries b ON b.id=l.beneficiary_id WHERE ".implode(" AND ",array_merge(["l.year=?"],$bWhere)));
        $stmt->execute($lnsParams); $lnsCount = (int)$stmt->fetchColumn();

        $this->success(['year'=>$year,'vitamin_a'=>$vitaminACount,'mnp'=>$mnpCount,'lns_sq'=>$lnsCount]);
    }

    public function outcome(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $db   = Database::getInstance();
        $year = (int)($_GET['year'] ?? date('Y'));
        $role = strtolower($user['role'] ?? '');
        $brgy = in_array($role,['bhw','encoder']) ? ($user['barangay']??null) : ($_GET['barangay']??null);

        $where = ['b.deleted_at IS NULL','a.assessment_year=?']; $params = [$year];
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
            $where = ['b.deleted_at IS NULL','a.assessment_year=?']; $params = [$yr];
            if ($brgy) { $where[] = 'b.barangay=?'; $params[] = $brgy; }
            $stmt = $db->prepare("SELECT a.nutritional_status, COUNT(*) as cnt FROM assessments a JOIN beneficiaries b ON b.id=a.beneficiary_id WHERE ".implode(' AND ',$where)." GROUP BY a.nutritional_status");
            $stmt->execute($params);
            $result[$yr] = [];
            foreach ($stmt->fetchAll() as $r) $result[$yr][$r['nutritional_status']] = (int)$r['cnt'];
        }
        $this->success(['year1'=>$year1,'year2'=>$year2,'data'=>$result]);
    }

    public function distribution(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $db   = Database::getInstance();
        $year = (int)($_GET['year'] ?? date('Y'));
        $period = $_GET['period'] ?? '';
        $role = strtolower($user['role'] ?? '');
        $brgy = in_array($role,['bhw','encoder']) ? ($user['barangay']??null) : ($_GET['barangay']??null);

        $where = ['b.deleted_at IS NULL','a.assessment_year=?']; $params = [$year];
        if ($period) { $where[] = 'a.period=?'; $params[] = $period; }
        if ($brgy)   { $where[] = 'b.barangay=?'; $params[] = $brgy; }

        $stmt = $db->prepare("SELECT b.barangay, a.nutritional_status, COUNT(*) as cnt FROM assessments a JOIN beneficiaries b ON b.id=a.beneficiary_id WHERE ".implode(' AND ',$where)." GROUP BY b.barangay, a.nutritional_status ORDER BY b.barangay");
        $stmt->execute($params);
        $byBarangay = [];
        foreach ($stmt->fetchAll() as $r) $byBarangay[$r['barangay']][$r['nutritional_status']] = (int)$r['cnt'];

        $this->success(['year'=>$year,'period'=>$period,'by_barangay'=>$byBarangay]);
    }
}
