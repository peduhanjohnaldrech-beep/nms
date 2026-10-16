<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;
use App\Models\Beneficiary;
use App\Models\ProgramEnrollment;
use App\Models\MnpRecord;
use App\Models\LnsSqRecord;

class ProgramApiController extends ApiController
{
    public function opt(): void
    {
        $this->requireApiAuth();
        $db   = Database::getInstance();

        $year   = (int)($_GET['year']   ?? date('Y'));
        $period = $_GET['period']        ?? '';
        $brgy   = $this->scopedBarangay($_GET['barangay'] ?? null);

        $purok    = $_GET['purok'] ?? '';
        $bnsFilter = Beneficiary::BNS_SUBMITTED_FILTER;
        $where  = ['b.deleted_at IS NULL', "b.validation_status = 'validated'", $bnsFilter, 'a.assessment_year = ?'];
        $params = [$year];

        if ($period) { $where[] = 'a.period = ?';                        $params[] = $period; }
        if ($brgy)   { $where[] = 'b.barangay = ?';                      $params[] = $brgy; }
        if ($purok)  { $where[] = 'LOWER(b.purok_zone) = LOWER(?)';      $params[] = $purok; }

        $stmt = $db->prepare(
            "SELECT a.id, a.assessment_date, a.age_in_months, a.weight_kg, a.height_cm,
                    a.nutritional_status, a.weight_for_age_zscore, a.period, a.assessment_year,
                    b.last_name, b.first_name, b.barangay, b.sex, b.date_of_birth
             FROM assessments a
             JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY b.barangay, a.nutritional_status, b.last_name"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $counts = ['Normal'=>0,'UW'=>0,'SUW'=>0,'OW'=>0,'OB'=>0];
        foreach ($rows as $r) {
            $s = $r['nutritional_status'] ?? 'Normal';
            if (isset($counts[$s])) $counts[$s]++;
        }

        $this->success(['assessments' => $rows, 'counts' => $counts, 'year' => $year, 'period' => $period]);
    }

    public function mns(): void
    {
        $this->requireApiAuth();
        $db   = Database::getInstance();

        $year  = (int)($_GET['year']  ?? date('Y'));
        $round = $_GET['round'] ?? 'April';
        $brgy  = $this->scopedBarangay($_GET['barangay'] ?? null);
        $tab   = $_GET['tab'] ?? 'vitaminA';

        $where  = [];
        $params = [];

        $asOfDate = $year < (int)date('Y') ? "{$year}-12-31" : date('Y-m-d');

        if ($tab === 'vitaminA') {
            $where[]  = 'v.year = ?'; $params[] = $year;
            $where[]  = 'v.round = ?'; $params[] = $round;
            if ($brgy) { $where[] = 'b.barangay = ?'; $params[] = $brgy; }
            $stmt = $db->prepare(
                "SELECT v.*, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.sex
                 FROM vitamin_a_records v
                 JOIN beneficiaries b ON b.id = v.beneficiary_id
                 WHERE b.deleted_at IS NULL AND b.validation_status = 'validated' AND " . implode(' AND ', $where) . "
                 ORDER BY b.barangay, b.last_name"
            );
            $stmt->execute($params);
            $rows    = $stmt->fetchAll();
            $eligible = (new ProgramEnrollment())->getEligibleForMNS($round, $year, $brgy ?? '');
            $this->success(['records' => $rows, 'eligible' => $eligible, 'tab' => $tab, 'year' => $year, 'round' => $round]);
        } elseif ($tab === 'mnp') {
            $where[]  = 'm.year = ?'; $params[] = $year;
            if ($brgy) { $where[] = 'b.barangay = ?'; $params[] = $brgy; }
            $stmt = $db->prepare(
                "SELECT m.*, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.sex
                 FROM mnp_records m
                 JOIN beneficiaries b ON b.id = m.beneficiary_id
                 WHERE b.deleted_at IS NULL AND b.validation_status = 'validated' AND " . implode(' AND ', $where) . "
                 ORDER BY m.date_given DESC"
            );
            $stmt->execute($params);
            $rows   = $stmt->fetchAll();
            $notYet = (new MnpRecord())->getNotYetReceived($year, $asOfDate, $brgy ?? '');
            $this->success(['records' => $rows, 'not_yet' => $notYet, 'tab' => $tab, 'year' => $year]);
        } elseif ($tab === 'lnssq') {
            $where[]  = 'l.year = ?'; $params[] = $year;
            if ($brgy) { $where[] = 'b.barangay = ?'; $params[] = $brgy; }
            $stmt = $db->prepare(
                "SELECT l.*, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.sex
                 FROM lns_sq_records l
                 JOIN beneficiaries b ON b.id = l.beneficiary_id
                 WHERE b.deleted_at IS NULL AND b.validation_status = 'validated' AND " . implode(' AND ', $where) . "
                 ORDER BY l.date_given DESC"
            );
            $stmt->execute($params);
            $rows   = $stmt->fetchAll();
            $notYet = (new LnsSqRecord())->getNotYetReceived($year, $asOfDate, $brgy ?? '');
            $this->success(['records' => $rows, 'not_yet' => $notYet, 'tab' => $tab, 'year' => $year]);
        } else {
            // defaulter
            $bnsFilter = \App\Models\Beneficiary::BNS_SUBMITTED_FILTER;
            $brgyWhere = $brgy ? " AND b.barangay = " . $db->quote($brgy) : '';
            $aprDate   = "{$year}-04-15";
            $octDate   = "{$year}-10-15";
            $asOfDate  = $year < (int)date('Y') ? "{$year}-12-31" : date('Y-m-d');
            $rows = $db->query(
                "SELECT b.id, b.last_name, b.first_name, b.barangay, b.purok_zone,
                        TIMESTAMPDIFF(MONTH, b.date_of_birth, CURDATE()) AS age_months,
                        (TIMESTAMPDIFF(MONTH, b.date_of_birth, '$aprDate') BETWEEN 6 AND 59) AS elig_vita_feb,
                        (TIMESTAMPDIFF(MONTH, b.date_of_birth, '$octDate') BETWEEN 6 AND 59) AS elig_vita_aug,
                        (TIMESTAMPDIFF(MONTH, b.date_of_birth, '$asOfDate') BETWEEN 6 AND 23) AS elig_mnp,
                        (TIMESTAMPDIFF(MONTH, b.date_of_birth, '$asOfDate') BETWEEN 6 AND 24) AS elig_lns,
                        EXISTS(SELECT 1 FROM vitamin_a_records v WHERE v.beneficiary_id = b.id AND v.round = 'April' AND v.year = $year) AS got_vita_feb,
                        EXISTS(SELECT 1 FROM vitamin_a_records v WHERE v.beneficiary_id = b.id AND v.round = 'October' AND v.year = $year) AS got_vita_aug,
                        EXISTS(SELECT 1 FROM mnp_records m WHERE m.beneficiary_id = b.id AND m.year = $year) AS got_mnp,
                        EXISTS(SELECT 1 FROM lns_sq_records l WHERE l.beneficiary_id = b.id AND l.year = $year) AS got_lns
                 FROM beneficiaries b
                 WHERE b.deleted_at IS NULL AND b.validation_status = 'validated'
                 AND $bnsFilter$brgyWhere
                 AND (
                     TIMESTAMPDIFF(MONTH, b.date_of_birth, '$aprDate') BETWEEN 6 AND 59
                     OR TIMESTAMPDIFF(MONTH, b.date_of_birth, '$octDate') BETWEEN 6 AND 59
                     OR TIMESTAMPDIFF(MONTH, b.date_of_birth, '$asOfDate') BETWEEN 6 AND 24
                 )
                 HAVING (elig_vita_feb AND NOT got_vita_feb)
                     OR (elig_vita_aug AND NOT got_vita_aug)
                     OR (elig_mnp AND NOT got_mnp)
                     OR (elig_lns AND NOT got_lns)
                 ORDER BY b.barangay, b.last_name"
            )->fetchAll();
            // Attach reasons
            if (!empty($rows)) {
                $ids = array_column($rows, 'id');
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $rStmt = $db->prepare(
                    "SELECT beneficiary_id, supplement, reason FROM mns_defaulter_reasons
                     WHERE beneficiary_id IN ($placeholders) AND year = ?"
                );
                $rStmt->execute(array_merge($ids, [$year]));
                $reasonMap = [];
                foreach ($rStmt->fetchAll() as $r) {
                    $reasonMap[$r['beneficiary_id']][$r['supplement']] = $r['reason'];
                }
                foreach ($rows as &$row) {
                    $row['reasons'] = $reasonMap[$row['id']] ?? (object)[];
                }
                unset($row);
            }
            $this->success(['defaulters' => $rows, 'tab' => 'defaulter', 'year' => $year]);
        }
    }

    // ─── DSP ───────────────────────────────────────────────

    public function dsp(): void
    {
        $this->requireApiAuth();
        $db     = Database::getInstance();
        $year   = (int)($_GET['year'] ?? date('Y'));
        $status = $_GET['status'] ?? '';
        $brgy   = $this->scopedBarangay($_GET['barangay'] ?? null);

        $bnsFilter2 = Beneficiary::BNS_SUBMITTED_FILTER;
        // Active enrollments span years; completed/dropped are filtered by cycle_year
        $where  = ["pe.program = 'DSP'", "b.validation_status = 'validated'", $bnsFilter2,
                   "(pe.status = 'Active' OR pe.cycle_year = ?)"];
        $params = [$year];
        if ($status) { $where[] = 'pe.status = ?'; $params[] = $status; }
        if ($brgy)   { $where[] = 'b.barangay = ?'; $params[] = $brgy; }

        $stmt = $db->prepare("
            SELECT pe.*, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.sex,
                   b.contact_number, b.mother_name
            FROM program_enrollments pe
            JOIN beneficiaries b ON b.id = pe.beneficiary_id
            WHERE b.deleted_at IS NULL AND " . implode(' AND ', $where) . "
            ORDER BY pe.status, b.last_name
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $counts = ['Active' => 0, 'Completed' => 0, 'Dropped' => 0];
        foreach ($rows as $r) { if (isset($counts[$r['status']])) $counts[$r['status']]++; }

        // Attach monitoring records for each enrollment (one extra batch query)
        $ids = array_column($rows, 'id');
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $monStmt = $db->prepare(
                "SELECT id, enrollment_id, monitoring_date, weight_kg, muac_cm, height_cm, nutritional_status, validation_status
                 FROM dsp_monitoring WHERE enrollment_id IN ($ph) ORDER BY monitoring_date ASC"
            );
            $monStmt->execute($ids);
            $monByEnrollment = [];
            foreach ($monStmt->fetchAll() as $m) {
                $monByEnrollment[$m['enrollment_id']][] = $m;
            }
            foreach ($rows as &$row) {
                $row['monitoring_records'] = $monByEnrollment[$row['id']] ?? [];
            }
            unset($row);
        }

        $this->success(['enrollments' => $rows, 'counts' => $counts, 'year' => $year]);
    }

    public function dspEnroll(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);
        $db   = Database::getInstance();
        $data = $this->body();
        if (empty($data['beneficiary_id']))  $this->error('beneficiary_id required', 422);
        if (empty($data['enrollment_date'])) $this->error('enrollment_date required', 422);
        $db->prepare("INSERT INTO program_enrollments (beneficiary_id,program,enrollment_date,cycle_year,status,intervention_type,pre_weight_kg,notes,enrolled_by) VALUES (?,'DSP',?,?,'Active',?,?,?,?)")
           ->execute([$data['beneficiary_id'],$data['enrollment_date'],$data['cycle_year']??date('Y'),$data['intervention_type']??null,$data['pre_weight_kg']??null,$data['notes']??null,$this->userId()]);
        $this->success([], 'Enrolled in DSP');
    }

    public function dspUpdate(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);
        $db   = Database::getInstance();
        $data = $this->body();
        if (empty($data['id'])) $this->error('id required', 422);
        $db->prepare("UPDATE program_enrollments SET post_weight_kg=?,notes=?,status=? WHERE id=? AND program='DSP'")
           ->execute([$data['post_weight_kg']??null,$data['notes']??null,$data['status']??'Active',$data['id']]);
        $this->success([], 'DSP record updated');
    }

    public function dspDischarge(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);
        $db   = Database::getInstance();
        $data = $this->body();
        if (empty($data['id'])) $this->error('id required', 422);
        $db->prepare("UPDATE program_enrollments SET status=?,end_date=?,post_weight_kg=?,notes=? WHERE id=? AND program='DSP'")
           ->execute([$data['status']??'Completed',$data['end_date']??date('Y-m-d'),$data['post_weight_kg']??null,$data['notes']??null,$data['id']]);
        $this->success([], 'DSP discharge recorded');
    }

    // ─── DSP Monitoring ─────────────────────────────────────

    public function dspMonitoringCreate(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);

        $data = $this->body();
        if (empty($data['enrollment_id']))  $this->error('enrollment_id required', 422);
        if (empty($data['beneficiary_id'])) $this->error('beneficiary_id required', 422);
        if (empty($data['weight_kg']))      $this->error('weight_kg required', 422);
        if (empty($data['muac_cm']))        $this->error('muac_cm required', 422);

        $db   = Database::getInstance();
        $bene = $db->prepare('SELECT sex, date_of_birth FROM beneficiaries WHERE id = ? AND deleted_at IS NULL');
        $bene->execute([$data['beneficiary_id']]);
        $b = $bene->fetch(\PDO::FETCH_ASSOC);
        if (!$b) $this->error('Beneficiary not found', 404);

        $monitoringDate = $data['monitoring_date'] ?? date('Y-m-d');
        $ageMonths      = isset($data['age_in_months'])
            ? (int)$data['age_in_months']
            : \DateHelper::ageInMonths($b['date_of_birth'], $monitoringDate);

        // Enforce one check per month per enrollment
        $dupCheck = $db->prepare(
            "SELECT COUNT(*) FROM dsp_monitoring
             WHERE enrollment_id = ?
               AND YEAR(monitoring_date)  = YEAR(?)
               AND MONTH(monitoring_date) = MONTH(?)"
        );
        $dupCheck->execute([(int)$data['enrollment_id'], $monitoringDate, $monitoringDate]);
        if ((int)$dupCheck->fetchColumn() > 0) {
            $this->error('A monitoring record already exists for this enrollment this month.', 409);
        }

        $model = new \App\Models\DspMonitoring();
        $id    = $model->createWithZScore([
            'local_id'         => $data['local_id'] ?? null,
            'enrollment_id'    => (int)$data['enrollment_id'],
            'beneficiary_id'   => (int)$data['beneficiary_id'],
            'monitoring_date'  => $monitoringDate,
            'age_in_months'    => $ageMonths,
            'sex'              => $data['sex'] ?? $b['sex'],
            'weight_kg'        => (float)$data['weight_kg'],
            'height_cm'        => isset($data['height_cm']) ? (float)$data['height_cm'] : null,
            'muac_cm'          => (float)$data['muac_cm'],
            'monitored_by'     => $data['monitored_by'] ?? $this->apiUser['full_name'],
            'remarks'          => $data['remarks'] ?? null,
            'validation_status' => $this->isFieldWorker() ? 'pending' : 'validated',
            'validated_by'     => $this->isFieldWorker() ? null : $this->userId(),
            'validated_at'     => $this->isFieldWorker() ? null : date('Y-m-d H:i:s'),
            'created_by'       => $this->userId(),
        ]);

        $this->success(['id' => $id], 'Monitoring record saved');
    }

    public function dspMonitoringList(): void
    {
        $this->requireApiAuth();
        $db   = Database::getInstance();
        $brgy = $this->scopedBarangay($_GET['barangay'] ?? null);

        $where  = ["pe.program = 'DSP'", "pe.status = 'Active'"];
        $params = [];
        if ($brgy) { $where[] = 'b.barangay = ?'; $params[] = $brgy; }
        if (!empty($_GET['enrollment_id'])) { $where[] = 'dm.enrollment_id = ?'; $params[] = (int)$_GET['enrollment_id']; }

        $stmt = $db->prepare(
            "SELECT dm.*, b.last_name, b.first_name, b.barangay, pe.intervention_type
             FROM dsp_monitoring dm
             JOIN beneficiaries b ON b.id = dm.beneficiary_id
             JOIN program_enrollments pe ON pe.id = dm.enrollment_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY dm.monitoring_date DESC, dm.id DESC"
        );
        $stmt->execute($params);
        $this->success(['records' => $stmt->fetchAll()]);
    }

    // ─── MNS Actions ────────────────────────────────────────

    public function mnsVitaminA(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);
        $db   = Database::getInstance();
        $data = $this->body();
        if (empty($data['beneficiary_id']))   $this->error('beneficiary_id required', 422);
        if (empty($data['distribution_date'])) $this->error('distribution_date required', 422);
        $db->prepare("INSERT INTO vitamin_a_records (beneficiary_id,distribution_date,round,year,dosage_iu,capsule_color,administered_by,created_by) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$data['beneficiary_id'],$data['distribution_date'],$data['round']??'April',$data['year']??date('Y'),$data['dosage_iu']??100000,$data['capsule_color']??'Blue',$data['administered_by']??$this->apiUser['full_name'],$this->userId()]);
        $this->success([], 'Vitamin A record saved');
    }

    public function mnsMnp(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);
        $db   = Database::getInstance();
        $data = $this->body();
        if (empty($data['beneficiary_id'])) $this->error('beneficiary_id required', 422);
        if (empty($data['date_given']))     $this->error('date_given required', 422);
        $this->validateDistributionDate($data['date_given']);
        $sachets = isset($data['sachets_distributed']) && $data['sachets_distributed'] !== null ? (int)$data['sachets_distributed'] : null;
        $db->prepare("INSERT INTO mnp_records (beneficiary_id,date_given,year,age_group,completed_routine,sachets_distributed,notes,given_by) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$data['beneficiary_id'],$data['date_given'],$data['year']??date('Y'),$data['age_group']??'6-11 months',(int)($data['completed_routine']??0),$sachets,$data['notes']??null,$this->userId()]);
        $this->success([], 'MNP record saved');
    }

    public function mnsLnsSq(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);
        $db   = Database::getInstance();
        $data = $this->body();
        if (empty($data['beneficiary_id'])) $this->error('beneficiary_id required', 422);
        if (empty($data['date_given']))     $this->error('date_given required', 422);
        $this->validateDistributionDate($data['date_given']);
        $sachets = isset($data['sachets_distributed']) && $data['sachets_distributed'] !== null ? (int)$data['sachets_distributed'] : null;
        $db->prepare("INSERT INTO lns_sq_records (beneficiary_id,date_given,year,age_group,completed_routine,sachets_distributed,notes,given_by) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$data['beneficiary_id'],$data['date_given'],$data['year']??date('Y'),$data['age_group']??'6-11 months',(int)($data['completed_routine']??0),$sachets,$data['notes']??null,$this->userId()]);
        $this->success([], 'LNS-SQ record saved');
    }

    private function validateDistributionDate(string $date): void
    {
        $given = strtotime($date);
        if ($given === false) $this->error('Invalid date', 422);
        $cutoff = strtotime('-30 days');
        if ($given < $cutoff) $this->error('Date is too far in the past. Maximum 30 days back allowed.', 422);
        if ($given > strtotime('+1 day')) $this->error('Date cannot be in the future', 422);
    }

    // ─── MNS Delete / Complete ─────────────────────────────

    public function mnsVitaminADelete(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);
        Database::getInstance()->prepare("DELETE FROM vitamin_a_records WHERE id = ?")->execute([$id]);
        $this->success([], 'Vitamin A record deleted');
    }

    public function mnpComplete(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);
        Database::getInstance()->prepare("UPDATE mnp_records SET completed_routine = 1 WHERE id = ?")->execute([$id]);
        $this->success([], 'MNP routine marked complete');
    }

    public function lnsSqComplete(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);
        Database::getInstance()->prepare("UPDATE lns_sq_records SET completed_routine = 1 WHERE id = ?")->execute([$id]);
        $this->success([], 'LNS-SQ routine marked complete');
    }

    // ─── MNS Defaulter Reason ──────────────────────────────

    public function mnsDefaulterReason(): void
    {
        $this->requireApiAuth();
        $db   = Database::getInstance();
        $data = $this->body();
        if (empty($data['beneficiary_id'])) $this->error('beneficiary_id required', 422);
        if (empty($data['supplement']))     $this->error('supplement required', 422);
        if (empty($data['reason']))         $this->error('reason required', 422);
        $year = (int)($data['year'] ?? date('Y'));
        $db->prepare(
            "INSERT INTO mns_defaulter_reasons (beneficiary_id, year, supplement, reason, noted_by)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE reason = VALUES(reason), noted_by = VALUES(noted_by), noted_at = NOW()"
        )->execute([$data['beneficiary_id'], $year, $data['supplement'], $data['reason'], $this->userId()]);
        $this->success([], 'Reason saved');
    }

    // ─── MNS Distribution Completion ───────────────────────

    public function mnsDistributionComplete(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['bns','bhw','nutritionist','admin']);
        $db   = Database::getInstance();
        $data = $this->body();
        if (empty($data['supplement'])) $this->error('supplement required (mnp or lnssq)', 422);
        $year    = (int)($data['year'] ?? date('Y'));
        $user    = $this->apiUser;
        $barangay = $user['barangay'] ?? '';
        if (empty($barangay)) $this->error('User has no barangay assigned', 422);
        $db->prepare(
            "INSERT INTO mns_distribution_completions (user_id, barangay, year, supplement, note)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE note = VALUES(note), completed_at = NOW()"
        )->execute([$this->userId(), $barangay, $year, $data['supplement'], $data['note'] ?? null]);
        $this->success([], 'Distribution marked as complete');
    }

    public function mnsDistributionStatus(): void
    {
        $this->requireApiAuth();
        $db   = Database::getInstance();
        $year = (int)($_GET['year'] ?? date('Y'));
        $brgy = $this->scopedBarangay($_GET['barangay'] ?? null);
        $where  = ['dc.year = ?'];
        $params = [$year];
        if ($brgy) { $where[] = 'dc.barangay = ?'; $params[] = $brgy; }
        $stmt = $db->prepare(
            "SELECT dc.*, u.full_name, u.username FROM mns_distribution_completions dc
             JOIN users u ON u.id = dc.user_id
             WHERE " . implode(' AND ', $where) . " ORDER BY dc.completed_at DESC"
        );
        $stmt->execute($params);
        $this->success(['completions' => $stmt->fetchAll()]);
    }

    // ─── Generic / Custom Programs ──────────────────────────

    public function genericList(string $code): void
    {
        $this->requireApiAuth();
        $db   = Database::getInstance();
        $code = strtoupper($code);

        if (in_array($code, ['OPT','DSP','MNS'])) $this->error('Use dedicated endpoint', 400);

        $year   = (int)($_GET['year'] ?? date('Y'));
        $status = $_GET['status'] ?? '';
        $brgy   = $this->scopedBarangay($_GET['barangay'] ?? null);

        $bnsFilter3 = Beneficiary::BNS_SUBMITTED_FILTER;
        $where  = ["pe.program = ?", "pe.cycle_year = ?", "b.validation_status = 'validated'", $bnsFilter3];
        $params = [$code, $year];
        if ($status) { $where[] = 'pe.status = ?'; $params[] = $status; }
        if ($brgy)   { $where[] = 'b.barangay = ?'; $params[] = $brgy; }

        $stmt = $db->prepare("
            SELECT pe.*, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.sex
            FROM program_enrollments pe
            JOIN beneficiaries b ON b.id = pe.beneficiary_id
            WHERE b.deleted_at IS NULL AND " . implode(' AND ', $where) . "
            ORDER BY pe.status, b.last_name
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $counts = ['Active' => 0, 'Completed' => 0, 'Dropped' => 0];
        foreach ($rows as $r) { if (isset($counts[$r['status']])) $counts[$r['status']]++; }

        $this->success(['enrollments' => $rows, 'counts' => $counts, 'year' => $year, 'code' => $code]);
    }

    public function genericEnroll(string $code): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);
        $code = strtoupper($code);
        $data = $this->body();
        if (empty($data['beneficiary_id'])) $this->error('beneficiary_id required', 422);

        $db = Database::getInstance();
        $chk = $db->prepare("SELECT id FROM program_enrollments WHERE beneficiary_id=? AND program=? AND status='Active'");
        $chk->execute([$data['beneficiary_id'], $code]);
        if ($chk->fetch()) $this->error('Already actively enrolled in this program', 422);

        $db->prepare("INSERT INTO program_enrollments (beneficiary_id,program,enrollment_date,cycle_year,status,notes,enrolled_by) VALUES (?,?,?,?,'Active',?,?)")
           ->execute([$data['beneficiary_id'], $code, $data['enrollment_date'] ?? date('Y-m-d'), $data['cycle_year'] ?? date('Y'), $data['notes'] ?? null, $this->userId()]);
        $this->success([], "Enrolled in $code");
    }

    public function genericDischarge(string $code): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);
        $code = strtoupper($code);
        $data = $this->body();
        if (empty($data['id'])) $this->error('id required', 422);

        Database::getInstance()
            ->prepare("UPDATE program_enrollments SET status=?,end_date=?,notes=? WHERE id=? AND program=?")
            ->execute([$data['status'] ?? 'Completed', $data['end_date'] ?? date('Y-m-d'), $data['notes'] ?? null, $data['id'], $code]);
        $this->success([], "Discharged from $code");
    }

    public function puroks(): void
    {
        $this->requireApiAuth();
        $barangay = $_GET['barangay'] ?? '';
        $puroks   = (new \App\Models\Beneficiary())->getPuroksByBarangay($barangay);
        $this->success(['puroks' => $puroks]);
    }

    private function scopedBarangay(?string $requested): ?string
    {
        if ($this->isBhw() || $this->isBns()) return $this->userBarangay();
        return $requested;
    }
}
