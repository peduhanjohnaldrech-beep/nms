<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;

class ProgramApiController extends ApiController
{
    public function opt(): void
    {
        $this->requireApiAuth();
        $db   = Database::getInstance();

        $year   = (int)($_GET['year']   ?? date('Y'));
        $period = $_GET['period']        ?? '';
        $brgy   = $this->scopedBarangay($_GET['barangay'] ?? null);

        $where  = ['b.deleted_at IS NULL', 'a.assessment_year = ?'];
        $params = [$year];

        if ($period) { $where[] = 'a.period = ?'; $params[] = $period; }
        if ($brgy)   { $where[] = 'b.barangay = ?'; $params[] = $brgy; }

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
        $round = $_GET['round'] ?? 'February';
        $brgy  = $this->scopedBarangay($_GET['barangay'] ?? null);
        $tab   = $_GET['tab'] ?? 'vitaminA';

        $where  = [];
        $params = [];

        if ($tab === 'vitaminA') {
            $where[]  = 'v.year = ?'; $params[] = $year;
            $where[]  = 'v.round = ?'; $params[] = $round;
            if ($brgy) { $where[] = 'b.barangay = ?'; $params[] = $brgy; }
            $stmt = $db->prepare(
                "SELECT v.*, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.sex
                 FROM vitamin_a_records v
                 JOIN beneficiaries b ON b.id = v.beneficiary_id
                 WHERE b.deleted_at IS NULL AND " . implode(' AND ', $where) . "
                 ORDER BY b.barangay, b.last_name"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            $this->success(['records' => $rows, 'tab' => $tab, 'year' => $year, 'round' => $round]);
        } elseif ($tab === 'mnp') {
            $where[]  = 'm.year = ?'; $params[] = $year;
            if ($brgy) { $where[] = 'b.barangay = ?'; $params[] = $brgy; }
            $stmt = $db->prepare(
                "SELECT m.*, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.sex
                 FROM mnp_records m
                 JOIN beneficiaries b ON b.id = m.beneficiary_id
                 WHERE b.deleted_at IS NULL AND " . implode(' AND ', $where) . "
                 ORDER BY m.date_given DESC"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            $this->success(['records' => $rows, 'tab' => $tab, 'year' => $year]);
        } else {
            // lnssq
            $where[]  = 'l.year = ?'; $params[] = $year;
            if ($brgy) { $where[] = 'b.barangay = ?'; $params[] = $brgy; }
            $stmt = $db->prepare(
                "SELECT l.*, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.sex
                 FROM lns_sq_records l
                 JOIN beneficiaries b ON b.id = l.beneficiary_id
                 WHERE b.deleted_at IS NULL AND " . implode(' AND ', $where) . "
                 ORDER BY l.date_given DESC"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            $this->success(['records' => $rows, 'tab' => $tab, 'year' => $year]);
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

        $where  = ["pe.program = 'DSP'", "pe.cycle_year = ?"];
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
           ->execute([$data['beneficiary_id'],$data['distribution_date'],$data['round']??'February',$data['year']??date('Y'),$data['dosage_iu']??100000,$data['capsule_color']??'Blue',$data['administered_by']??$this->apiUser['full_name'],$this->userId()]);
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
        $db->prepare("INSERT INTO mnp_records (beneficiary_id,date_given,year,age_group,completed_routine,notes,given_by) VALUES (?,?,?,?,?,?,?)")
           ->execute([$data['beneficiary_id'],$data['date_given'],$data['year']??date('Y'),$data['age_group']??'6-11 months',(int)($data['completed_routine']??0),$data['notes']??null,$this->userId()]);
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
        $db->prepare("INSERT INTO lns_sq_records (beneficiary_id,date_given,year,age_group,completed_routine,notes,given_by) VALUES (?,?,?,?,?,?,?)")
           ->execute([$data['beneficiary_id'],$data['date_given'],$data['year']??date('Y'),$data['age_group']??'6-11 months',(int)($data['completed_routine']??0),$data['notes']??null,$this->userId()]);
        $this->success([], 'LNS-SQ record saved');
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

        $where  = ["pe.program = ?", "pe.cycle_year = ?"];
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

    private function scopedBarangay(?string $requested): ?string
    {
        if ($this->isBhw() || $this->isBns()) return $this->userBarangay();
        return $requested;
    }
}
