<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use App\Models\ProgramEnrollment;
use App\Models\VitaminARecord;
use App\Models\MnpRecord;
use App\Models\LnsSqRecord;
use App\Models\Assessment;
use App\Models\Program;
use App\Models\DispensingRecord;
use App\Models\Beneficiary;

class ProgramController extends Controller
{
    private ProgramEnrollment $enrollmentModel;

    public function __construct()
    {
        $this->enrollmentModel = new ProgramEnrollment();
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->redirect('/programs/opt');
    }

    public function opt(): void
    {
        $this->requireAuth();

        $year     = (int)($_GET['year']     ?? date('Y'));
        $period   = $_GET['period']         ?? '';
        $barangay = trim($_GET['barangay']  ?? '');
        $purok    = trim($_GET['purok']     ?? '');

        // BNS/BHW scoped to their barangay
        $role = Session::get('user_role');
        if (in_array($role, ['bhw', 'bns'])) {
            $barangay = Session::get('user_barangay', '');
        }

        $statusData = (new Assessment())->getNutritionalStatus($year, $period);

        $db     = Database::getInstance();
        $params = [$year];
        $where  = 'a.assessment_year = ?';

        if ($period)     { $where .= ' AND a.period = ?';                       $params[] = $period; }
        if ($barangay)   { $where .= ' AND b.barangay = ?';                     $params[] = $barangay; }
        if ($purok)      { $where .= ' AND LOWER(b.purok_zone) = LOWER(?)';     $params[] = $purok; }

        $stmt = $db->prepare(
            "SELECT a.*, b.last_name, b.first_name, b.middle_name, b.barangay, b.sex, b.date_of_birth
             FROM assessments a JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL AND b.validation_status = 'validated'
               AND NOT (b.source = 'Mobile' AND b.submitted_at IS NULL AND EXISTS (SELECT 1 FROM users u WHERE u.id = b.created_by AND LOWER(u.role) = 'bns'))
               AND TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) <= 59
               AND $where
               AND a.id = (
                 SELECT id FROM assessments
                 WHERE beneficiary_id = a.beneficiary_id
                   AND assessment_year = a.assessment_year
                   AND period = a.period
                 ORDER BY assessment_date DESC, id DESC
                 LIMIT 1
               )
             ORDER BY b.barangay, a.nutritional_status, b.last_name"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Eligible: children who were 0–59 months at any point during the selected year
        $eligibleParams = ["{$year}-12-31", "{$year}-01-01"];
        $eligibleWhere  = "b.deleted_at IS NULL
            AND b.validation_status = 'validated'
            AND " . \App\Models\Beneficiary::BNS_SUBMITTED_FILTER . "
            AND b.date_of_birth <= ?
            AND b.date_of_birth >= DATE_SUB(?, INTERVAL 59 MONTH)";
        if ($barangay) { $eligibleWhere .= ' AND b.barangay = ?';                    $eligibleParams[] = $barangay; }
        if ($purok)    { $eligibleWhere .= ' AND LOWER(b.purok_zone) = LOWER(?)';    $eligibleParams[] = $purok; }

        $eligStmt = $db->prepare("SELECT COUNT(*) FROM beneficiaries b WHERE $eligibleWhere");
        $eligStmt->execute($eligibleParams);
        $totalEligible = (int)$eligStmt->fetchColumn();

        // Not yet weighed: eligible but no assessment this period/year
        $nywParams    = $eligibleParams;
        $nywParams[]  = $year;
        $periodClause = '';
        if ($period) { $periodClause = ' AND a.period = ?'; $nywParams[] = $period; }
        $nywStmt = $db->prepare(
            "SELECT b.id, b.last_name, b.first_name, b.barangay, b.sex, b.date_of_birth,
                    TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) AS age_months
             FROM beneficiaries b
             WHERE $eligibleWhere
             AND TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) <= 59
             AND b.id NOT IN (
                 SELECT a.beneficiary_id FROM assessments a WHERE a.assessment_year = ?$periodClause
             )
             ORDER BY b.barangay, b.last_name"
        );
        $nywStmt->execute($nywParams);
        $notYetWeighed = $nywStmt->fetchAll();

        $beneModel = new Beneficiary();
        $barangays = $beneModel->getAllBarangays();
        $puroks    = $beneModel->getPuroksByBarangay($barangay);

        $this->view('programs/opt', [
            'rows'           => $rows,
            'statusData'     => $statusData,
            'year'           => $year,
            'period'         => $period,
            'totalEligible'  => $totalEligible,
            'notYetWeighed'  => $notYetWeighed,
            'barangays'      => $barangays,
            'puroks'         => $puroks,
            'filterBarangay' => $barangay,
            'filterPurok'    => $purok,
        ]);
    }

    public function dsp(): void
    {
        $this->requireAuth();

        $beneModel   = new Beneficiary();
        $barangay    = trim($_GET['barangay']    ?? '');
        $purok       = trim($_GET['purok']       ?? '');
        $historyYear = (int)($_GET['history_year'] ?? date('Y'));

        $eligible         = $this->enrollmentModel->getEligibleForDSP($barangay, $purok);
        $active           = $this->enrollmentModel->getActive('DSP', $barangay, $purok);
        $notEnrolled      = $this->enrollmentModel->getNotEnrolledInProgram('DSP');
        $readyToDischarge = $this->enrollmentModel->getReadyToDischarge($barangay, $purok);
        $history          = $this->enrollmentModel->getHistory('DSP', $historyYear, $barangay, $purok);

        // Batch-fetch monitoring records for active + history enrollments
        $monitoringByEnrollment = [];
        $allEnrollmentIds = array_unique(array_merge(
            array_column($active, 'id'),
            array_column($history, 'id')
        ));
        if (!empty($allEnrollmentIds)) {
            $ph      = implode(',', array_fill(0, count($allEnrollmentIds), '?'));
            $monStmt = Database::getInstance()->prepare(
                "SELECT id, enrollment_id, monitoring_date, weight_kg, height_cm, muac_cm,
                        nutritional_status, wflh_status, monitored_by, validation_status
                 FROM dsp_monitoring WHERE enrollment_id IN ($ph) ORDER BY monitoring_date ASC"
            );
            $monStmt->execute($allEnrollmentIds);
            foreach ($monStmt->fetchAll() as $m) {
                $monitoringByEnrollment[(int)$m['enrollment_id']][] = $m;
            }
        }

        $barangays = $beneModel->getAllBarangays();
        $puroks    = $beneModel->getPuroksByBarangay($barangay);

        $this->view('programs/dsp', [
            'eligible'               => $eligible,
            'active'                 => $active,
            'notEnrolled'            => $notEnrolled,
            'readyToDischarge'       => $readyToDischarge,
            'history'                => $history,
            'historyYear'            => $historyYear,
            'monitoringByEnrollment' => $monitoringByEnrollment,
            'barangays'              => $barangays,
            'puroks'                 => $puroks,
            'filterBarangay'         => $barangay,
            'filterPurok'            => $purok,
        ]);
    }

    public function dspMasterlist(): void
    {
        $this->requireAuth();
        $db       = Database::getInstance();
        $role     = \Core\Session::get('user_role');
        $barangay = trim($_GET['barangay'] ?? '');
        $year     = (int)($_GET['year'] ?? date('Y'));

        // BNS/BHW scoped to their barangay
        if (in_array($role, ['bhw', 'bns'])) {
            $barangay = \Core\Session::get('user_barangay', '');
        }

        $where  = "pe.program = 'DSP' AND pe.cycle_year = ?";
        $params = [$year];
        if ($barangay !== '') { $where .= ' AND b.barangay = ?'; $params[] = $barangay; }

        $stmt = $db->prepare(
            "SELECT pe.id, pe.enrollment_date, pe.pre_weight_kg, pe.post_weight_kg,
                    pe.status, pe.end_date, pe.intervention_type, pe.cycle_year,
                    (SELECT COUNT(*) FROM program_enrollments pe2
                     WHERE pe2.beneficiary_id = pe.beneficiary_id AND pe2.program = pe.program AND pe2.id <= pe.id) AS cycle_number,
                    b.last_name, b.first_name, b.middle_name, b.date_of_birth, b.sex,
                    b.barangay, b.purok_zone,
                    TIMESTAMPDIFF(MONTH, b.date_of_birth, pe.enrollment_date) AS age_at_enrollment
             FROM program_enrollments pe
             JOIN beneficiaries b ON b.id = pe.beneficiary_id
             WHERE b.deleted_at IS NULL AND b.validation_status = 'validated' AND $where
             ORDER BY b.barangay, b.last_name, b.first_name"
        );
        $stmt->execute($params);
        $enrollments = $stmt->fetchAll();

        // Batch-fetch validated monitoring records
        $monByEnrollment = [];
        $ids = array_column($enrollments, 'id');
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $monStmt = $db->prepare(
                "SELECT enrollment_id, monitoring_date, weight_kg, muac_cm, nutritional_status, wflh_status
                 FROM dsp_monitoring
                 WHERE enrollment_id IN ($ph) AND validation_status = 'validated'
                 ORDER BY monitoring_date ASC"
            );
            $monStmt->execute($ids);
            foreach ($monStmt->fetchAll() as $m) {
                $monByEnrollment[(int)$m['enrollment_id']][] = $m;
            }
        }

        // Attach baseline / mid / endline to each enrollment
        foreach ($enrollments as &$e) {
            $recs = $monByEnrollment[$e['id']] ?? [];
            $n    = count($recs);
            $e['check_count'] = $n;
            $e['baseline'] = $n >= 1 ? $recs[0]           : null;
            $e['midpoint'] = $n >= 3 ? $recs[(int)($n/2)] : ($n === 2 ? $recs[0] : null);
            $e['endline']  = $n >= 1 ? $recs[$n - 1]      : null;
        }
        unset($e);

        $barangays = (new Beneficiary())->getAllBarangays();
        $yStmt     = $db->query("SELECT DISTINCT cycle_year FROM program_enrollments WHERE program = 'DSP' ORDER BY cycle_year DESC");
        $years     = $yStmt->fetchAll(\PDO::FETCH_COLUMN);
        if (empty($years)) $years = [date('Y')];

        $this->view('programs/dsp_masterlist', [
            'enrollments'    => $enrollments,
            'barangays'      => $barangays,
            'years'          => $years,
            'filterBarangay' => $barangay,
            'filterYear'     => $year,
        ]);
    }

    public function dspMonitoringCreate(): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/dsp'); }
        $this->validateCsrf();

        $enrollmentId   = (int)($_POST['enrollment_id']   ?? 0);
        $beneficiaryId  = (int)($_POST['beneficiary_id']  ?? 0);
        $dob            = $_POST['date_of_birth']         ?? '';
        $sex            = $_POST['sex']                   ?? '';
        $monitoringDate = $_POST['monitoring_date']       ?? date('Y-m-d');
        $weightKg       = (float)($_POST['weight_kg']     ?? 0);
        $heightCm       = !empty($_POST['height_cm'])     ? (float)$_POST['height_cm'] : null;
        $muacCm         = !empty($_POST['muac_cm'])       ? (float)$_POST['muac_cm']   : null;

        if (!$enrollmentId || !$beneficiaryId || $weightKg <= 0 || $muacCm === null) {
            Session::flash('error', 'Weight and MUAC are required.');
            $this->redirect('/programs/dsp');
        }

        $ageMonths = \DateHelper::ageInMonths($dob, $monitoringDate);
        $model     = new \App\Models\DspMonitoring();
        $model->createWithZScore([
            'enrollment_id'    => $enrollmentId,
            'beneficiary_id'   => $beneficiaryId,
            'monitoring_date'  => $monitoringDate,
            'age_in_months'    => $ageMonths,
            'sex'              => $sex,
            'weight_kg'        => $weightKg,
            'height_cm'        => $heightCm,
            'muac_cm'          => $muacCm,
            'monitored_by'     => trim($_POST['monitored_by'] ?? ''),
            'remarks'          => trim($_POST['remarks'] ?? ''),
            'validation_status' => 'validated',
            'validated_by'     => Session::get('user_id'),
            'validated_at'     => date('Y-m-d H:i:s'),
            'created_by'       => Session::get('user_id'),
        ]);

        Session::flash('success', 'DSP monitoring record saved.');
        $this->redirect('/programs/dsp');
    }

    public function dspEnrollAll(): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/dsp'); }
        $this->validateCsrf();

        // Fetch all latest validated assessments for eligible beneficiaries
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT a.id FROM assessments a
             JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL
               AND b.validation_status = 'validated'
               AND a.validation_status = 'validated'
               AND TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) <= 59
               AND (a.wflh_status IN ('SW','MW') OR a.nutritional_status IN ('SUW','UW'))
               AND a.id = (
                   SELECT id FROM assessments
                   WHERE beneficiary_id = b.id
                   ORDER BY assessment_date DESC, id DESC LIMIT 1
               )
               AND b.id NOT IN (
                   SELECT beneficiary_id FROM program_enrollments WHERE program = 'DSP' AND status = 'Active'
               )"
        );
        $stmt->execute();
        $assessmentIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $enrolled = 0;
        foreach ($assessmentIds as $assessmentId) {
            if ((new ProgramEnrollment())->autoEnrollDSP((int)$assessmentId)) {
                $enrolled++;
            }
        }

        \ActivityLog::log('dsp_enroll_all', "Bulk-enrolled $enrolled beneficiar" . ($enrolled === 1 ? 'y' : 'ies') . " in DSP");
        Session::flash($enrolled > 0 ? 'success' : 'info',
            $enrolled > 0 ? "$enrolled beneficiar" . ($enrolled === 1 ? 'y' : 'ies') . " enrolled in DSP." : 'No new eligible beneficiaries found.');
        $this->redirect('/programs/dsp');
    }

    public function dspEnroll(): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/dsp'); }
        $this->validateCsrf();

        $beneficiaryId    = (int)($_POST['beneficiary_id'] ?? 0);
        $interventionType = trim($_POST['intervention_type'] ?? '');
        $preWeight        = !empty($_POST['pre_weight_kg']) ? (float)$_POST['pre_weight_kg'] : null;

        $extra = [
            'enrolled_by' => Session::get('user_id'),
            'notes'       => trim($_POST['notes'] ?? ''),
        ];
        if ($interventionType) $extra['intervention_type'] = $interventionType;
        if ($preWeight !== null) $extra['pre_weight_kg'] = $preWeight;

        $result = $this->enrollmentModel->enrollBeneficiary($beneficiaryId, 'DSP', $extra);
        Session::flash($result ? 'success' : 'error', $result ? 'Beneficiary enrolled in DSP.' : 'Already enrolled in DSP.');
        $this->redirect('/programs/dsp');
    }

   public function dspUpdate(): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/dsp'); }
        $this->validateCsrf();

        $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
        $data = [];
        if (isset($_POST['pre_weight_kg'])  && $_POST['pre_weight_kg']  !== '') $data['pre_weight_kg']  = (float)$_POST['pre_weight_kg'];
        if (isset($_POST['post_weight_kg']) && $_POST['post_weight_kg'] !== '') $data['post_weight_kg'] = (float)$_POST['post_weight_kg'];
        if (!empty($_POST['intervention_type'])) $data['intervention_type'] = trim($_POST['intervention_type']);

        if ($enrollmentId && $data) {
            $this->enrollmentModel->update($enrollmentId, $data);

            if (!empty($data['post_weight_kg'])) {
                $enrollment = $this->enrollmentModel->findById($enrollmentId);
                if ($enrollment) {
                    $db   = Database::getInstance();
                    $stmt = $db->prepare("SELECT * FROM beneficiaries WHERE id = ?");
                    $stmt->execute([$enrollment['beneficiary_id']]);
                    $ben  = $stmt->fetch();

                    $prev = $db->prepare("SELECT * FROM assessments WHERE beneficiary_id = ? ORDER BY assessment_date DESC LIMIT 1");
                    $prev->execute([$enrollment['beneficiary_id']]);
                    $prev = $prev->fetch();

                    if ($ben && $prev) {
                        $today     = date('Y-m-d');
                        $ageMonths = \DateHelper::ageInMonths($ben['date_of_birth'], $today);
                        $month     = (int) date('n');
                        $period    = $month <= 6 ? 'January' : 'July';

                        (new Assessment())->createWithZScore([
                            'beneficiary_id'  => $enrollment['beneficiary_id'],
                            'sex'             => $ben['sex'],
                            'assessment_date' => $today,
                            'age_in_months'   => $ageMonths,
                            'weight_kg'       => $data['post_weight_kg'],
                            'height_cm'       => $prev['height_cm'] ?? null,
                            'muac_cm'         => null,
                            'period'          => $period,
                            'assessment_year' => (int) date('Y'),
                            'remarks'         => 'Mid-cycle DSP weight update (auto-generated)',
                            'created_by'      => Session::get('user_id'),
                        ]);
                    }
                }
            }

            Session::flash('success', 'Enrollment updated.');
        }
        $this->redirect('/programs/dsp');
    }
    public function dspDischarge(): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/dsp'); }
        $this->validateCsrf();

        $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
        $action       = $_POST['action'] ?? '';
        $postWeight   = !empty($_POST['post_weight_kg']) ? (float)$_POST['post_weight_kg'] : null;

        if ($action === 'complete') {
            $this->enrollmentModel->complete($enrollmentId, $postWeight);

            if ($postWeight !== null) {
                $enrollment = $this->enrollmentModel->findById($enrollmentId);
                if ($enrollment) {
                    $beneficiaryId = (int)$enrollment['beneficiary_id'];
                    $db   = Database::getInstance();
                    $stmt = $db->prepare("SELECT * FROM beneficiaries WHERE id = ?");
                    $stmt->execute([$beneficiaryId]);
                    $ben = $stmt->fetch();

                    $lastAssmt = $db->prepare(
                        "SELECT * FROM assessments WHERE beneficiary_id = ? ORDER BY assessment_date DESC LIMIT 1"
                    );
                    $lastAssmt->execute([$beneficiaryId]);
                    $prev = $lastAssmt->fetch();

                    if ($ben && $prev) {
                        $today      = date('Y-m-d');
                        $dob        = $ben['date_of_birth'];
                        $ageMonths  = \DateHelper::ageInMonths($dob, $today);
                        $month      = (int) date('n');
                        $period     = $month <= 6 ? 'January' : 'July';

                        (new Assessment())->createWithZScore([
                            'beneficiary_id'  => $beneficiaryId,
                            'sex'             => $ben['sex'],
                            'assessment_date' => $today,
                            'age_in_months'   => $ageMonths,
                            'weight_kg'       => $postWeight,
                            'height_cm'       => $prev['height_cm'] ?? null,
                            'muac_cm'         => null,
                            'period'          => $period,
                            'assessment_year' => (int) date('Y'),
                            'remarks'         => 'Post-DSP assessment (auto-generated from program completion)',
                            'created_by'      => Session::get('user_id'),
                        ]);
                    }
                }
            }

            Session::flash('success', 'Enrollment marked as completed.');
        } elseif ($action === 'drop') {
            $this->enrollmentModel->drop($enrollmentId);
            Session::flash('success', 'Enrollment dropped.');
        }

        $this->redirect('/programs/dsp');
    }

    public function mns(): void
    {
        $this->requireAuth();

        $round    = $_GET['round']    ?? 'April';
        $year     = (int)($_GET['year'] ?? date('Y'));
        $tab      = $_GET['tab']      ?? 'vitaminA';
        $barangay = trim($_GET['barangay'] ?? '');
        $purok    = trim($_GET['purok']    ?? '');

        // BNS/BHW scoped to their barangay
        $role = \Core\Session::get('user_role');
        if (in_array($role, ['bhw', 'bns'])) {
            $barangay = \Core\Session::get('user_barangay', '');
        }

        $mnpModel  = new MnpRecord();
        $lnsModel  = new LnsSqRecord();
        $vitaModel = new VitaminARecord();

        $eligible        = $this->enrollmentModel->getEligibleForMNS($round, $year, $barangay, $purok);
        $vitaRecords     = $vitaModel->getAllByRound($round, $year, $barangay, $purok);
        $vitaCoverage    = $vitaModel->getCoverageByBarangay($round, $year, $barangay);
        $mnpCompletion   = $mnpModel->getCompletionByBarangay($year, $barangay);
        $lnsCompletion   = $lnsModel->getCompletionByBarangay($year, $barangay);
        $mnpRecords      = $mnpModel->getAllByYear($year, $barangay, $purok);
        $lnsRecords      = $lnsModel->getAllByYear($year, $barangay, $purok);
        $asOfDate        = $year < (int)date('Y') ? "{$year}-12-31" : date('Y-m-d');
        $mnpNotYet       = $mnpModel->getNotYetReceived($year, $asOfDate, $barangay, $purok);
        $lnsNotYet       = $lnsModel->getNotYetReceived($year, $asOfDate, $barangay, $purok);

        $beneFilter = \App\Models\Beneficiary::BNS_SUBMITTED_FILTER;
        $brgyWhere  = $barangay !== '' ? " AND b.barangay = " . Database::getInstance()->quote($barangay) : '';
        $purokWhere = $purok    !== '' ? " AND LOWER(b.purok_zone) = LOWER(" . Database::getInstance()->quote($purok) . ")" : '';

        $defaulterList = [];
        if ($tab === 'defaulter') {
            $aprDate = "{$year}-04-15";
            $octDate = "{$year}-10-15";
            $defaulterList = Database::getInstance()->query(
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
                 AND $beneFilter$brgyWhere$purokWhere
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
        }

        $allBeneficiaries = Database::getInstance()->query(
            "SELECT id, last_name, first_name, barangay, date_of_birth FROM beneficiaries b
             WHERE b.deleted_at IS NULL AND b.validation_status = 'validated'
             AND TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) BETWEEN 6 AND 59
             AND $beneFilter$brgyWhere$purokWhere ORDER BY b.last_name, b.first_name"
        )->fetchAll();

        $beneModel = new Beneficiary();
        $barangays = $beneModel->getAllBarangays();
        $puroks    = $beneModel->getPuroksByBarangay($barangay);

        // Defaulter reasons (keyed by beneficiary_id + supplement)
        $defaulterReasons = [];
        if ($tab === 'defaulter' && !empty($defaulterList)) {
            $ids = array_column($defaulterList, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge($ids, [$year]);
            $stmt = Database::getInstance()->prepare(
                "SELECT beneficiary_id, supplement, reason FROM mns_defaulter_reasons
                 WHERE beneficiary_id IN ($placeholders) AND year = ?"
            );
            $stmt->execute($params);
            foreach ($stmt->fetchAll() as $r) {
                $defaulterReasons[$r['beneficiary_id']][$r['supplement']] = $r['reason'];
            }
        }

        // BNS coverage per barangay (grouped — multiple BNS can share a barangay)
        $bnsCoverage = Database::getInstance()->query(
            "SELECT u.barangay,
                    GROUP_CONCAT(u.full_name ORDER BY u.full_name SEPARATOR ', ') AS bns_names,
                    COUNT(u.id) AS bns_count,
                    (SELECT COUNT(*) FROM beneficiaries b
                     WHERE b.deleted_at IS NULL AND b.validation_status = 'validated'
                     AND TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) BETWEEN 6 AND 23
                     AND b.barangay = u.barangay) AS eligible_count,
                    (SELECT COUNT(*) FROM mnp_records m
                     JOIN beneficiaries b ON b.id = m.beneficiary_id
                     WHERE m.year = $year AND b.barangay = u.barangay) AS mnp_covered,
                    (SELECT COUNT(*) FROM lns_sq_records l
                     JOIN beneficiaries b ON b.id = l.beneficiary_id
                     WHERE l.year = $year AND b.barangay = u.barangay) AS lns_covered
             FROM users u
             WHERE u.role = 'bns' AND u.is_active = 1 AND u.barangay IS NOT NULL AND u.barangay != ''
             GROUP BY u.barangay
             ORDER BY u.barangay"
        )->fetchAll();

        // Distribution completions
        $completions = Database::getInstance()->prepare(
            "SELECT dc.barangay, dc.supplement, dc.completed_at, u.full_name
             FROM mns_distribution_completions dc
             JOIN users u ON u.id = dc.user_id
             WHERE dc.year = ?" . ($barangay !== '' ? " AND dc.barangay = " . Database::getInstance()->quote($barangay) : '') . "
             ORDER BY dc.completed_at DESC"
        );
        $completions->execute([$year]);
        $distributionCompletions = $completions->fetchAll();

        $this->view('programs/mns', [
            'eligible'               => $eligible,
            'vitaRecords'            => $vitaRecords,
            'coverage'               => $vitaCoverage,
            'mnpCompletion'          => $mnpCompletion,
            'lnsCompletion'          => $lnsCompletion,
            'mnpRecords'             => $mnpRecords,
            'lnsRecords'             => $lnsRecords,
            'mnpNotYet'              => $mnpNotYet,
            'lnsNotYet'              => $lnsNotYet,
            'allBeneficiaries'       => $allBeneficiaries,
            'round'                  => $round,
            'year'                   => $year,
            'tab'                    => $tab,
            'asOfDate'               => $asOfDate,
            'barangays'              => $barangays,
            'puroks'                 => $puroks,
            'filterBarangay'         => $barangay,
            'filterPurok'            => $purok,
            'defaulterList'          => $defaulterList,
            'defaulterReasons'       => $defaulterReasons,
            'bnsCoverage'            => $bnsCoverage,
            'distributionCompletions'=> $distributionCompletions,
        ]);
    }

    public function mnsVitaminA(): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/mns'); }
        $this->validateCsrf();

        $dob  = $_POST['date_of_birth'] ?? '';

        try {
            $bid   = (int)($_POST['beneficiary_id'] ?? 0);
            $date  = $_POST['distribution_date'] ?? date('Y-m-d');
            $round = $_POST['round'] ?? 'April';
            $year  = (int)($_POST['year'] ?? date('Y'));
            // Age at the actual distribution date, not today
            $ageMonths = \DateHelper::ageInMonths($dob, $date);
            $dose  = $ageMonths <= 11 ? '100,000 IU Vitamin A (Blue)' : '200,000 IU Vitamin A (Red)';

            (new VitaminARecord())->recordDistribution([
                'beneficiary_id'    => $bid,
                'distribution_date' => $date,
                'round'             => $round,
                'year'              => $year,
                'age_months'        => $ageMonths,
                'administered_by'   => trim($_POST['administered_by'] ?? ''),
                'created_by'        => Session::get('user_id'),
            ]);

            (new DispensingRecord())->recordDispensing([
                'beneficiary_id'  => $bid,
                'program'         => 'MNS',
                'supplement_type' => $dose,
                'quantity'        => 1,
                'unit'            => 'capsule',
                'date_dispensed'  => $date,
                'dispensed_by'    => Session::get('user_id'),
                'notes'           => "Vitamin A – {$round} {$year} round",
            ]);

            Session::flash('success', 'Vitamin A distribution recorded.');
        } catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/programs/mns?tab=vitaminA&round=' . urlencode($_POST['round'] ?? 'April') . '&year=' . ($_POST['year'] ?? date('Y')));
    }

    public function mnsVitaminADelete(int $id): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/mns?tab=vitaminA'); }
        $this->validateCsrf();

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM vitamin_a_records WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();

        if ($record) {
            $db->prepare("DELETE FROM vitamin_a_records WHERE id = ?")->execute([$id]);
            \ActivityLog::log('vita_delete', "Deleted Vitamin A record ID $id for beneficiary ID {$record['beneficiary_id']}");
            Session::flash('success', 'Vitamin A record deleted.');
        } else {
            Session::flash('error', 'Record not found.');
        }

        $year  = $_POST['year']  ?? date('Y');
        $round = $_POST['round'] ?? 'April';
        $this->redirect("/programs/mns?tab=vitaminA&year={$year}&round=" . urlencode($round));
    }

    public function mnsMnp(): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/mns'); }
        $this->validateCsrf();

        try {
            $bid  = (int)($_POST['beneficiary_id'] ?? 0);
            $date = $_POST['date_given'] ?? date('Y-m-d');
            $year = (int)($_POST['year'] ?? date('Y'));

            (new MnpRecord())->recordDistribution([
                'beneficiary_id'      => $bid,
                'given_by'            => Session::get('user_id'),
                'date_given'          => $date,
                'year'                => $year,
                'age_group'           => $_POST['age_group'] ?? '',
                'completed_routine'   => isset($_POST['completed_routine']) ? 1 : 0,
                'sachets_distributed' => ($_POST['sachets_distributed'] ?? '') !== '' ? (int)$_POST['sachets_distributed'] : null,
                'notes'               => trim($_POST['notes'] ?? ''),
            ]);

            (new DispensingRecord())->recordDispensing([
                'beneficiary_id'  => $bid,
                'program'         => 'MNS',
                'supplement_type' => 'MNP (Micronutrient Powder)',
                'quantity'        => 1,
                'unit'            => 'sachet',
                'date_dispensed'  => $date,
                'dispensed_by'    => Session::get('user_id'),
                'notes'           => trim($_POST['notes'] ?? ''),
            ]);

            Session::flash('success', 'MNP record saved.');
        } catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/programs/mns?tab=mnp&year=' . ($_POST['year'] ?? date('Y')));
    }

    public function mnsLnsSq(): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/mns'); }
        $this->validateCsrf();

        try {
            $bid  = (int)($_POST['beneficiary_id'] ?? 0);
            $date = $_POST['date_given'] ?? date('Y-m-d');
            $year = (int)($_POST['year'] ?? date('Y'));

            (new LnsSqRecord())->recordDistribution([
                'beneficiary_id'      => $bid,
                'given_by'            => Session::get('user_id'),
                'date_given'          => $date,
                'year'                => $year,
                'age_group'           => $_POST['age_group'] ?? '',
                'completed_routine'   => isset($_POST['completed_routine']) ? 1 : 0,
                'sachets_distributed' => ($_POST['sachets_distributed'] ?? '') !== '' ? (int)$_POST['sachets_distributed'] : null,
                'notes'               => trim($_POST['notes'] ?? ''),
            ]);

            (new DispensingRecord())->recordDispensing([
                'beneficiary_id'  => $bid,
                'program'         => 'MNS',
                'supplement_type' => 'LNS-SQ (Lipid-based Nutrient Supplement)',
                'quantity'        => 1,
                'unit'            => 'sachet',
                'date_dispensed'  => $date,
                'dispensed_by'    => Session::get('user_id'),
                'notes'           => trim($_POST['notes'] ?? ''),
            ]);

            Session::flash('success', 'LNS-SQ record saved.');
        } catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/programs/mns?tab=lnssq&year=' . ($_POST['year'] ?? date('Y')));
    }

    public function mnsMnpComplete(int $id): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/mns?tab=mnp'); }
        $this->validateCsrf();

        $year = (int)($_POST['year'] ?? date('Y'));
        Database::getInstance()->prepare(
            "UPDATE mnp_records SET completed_routine = 1 WHERE id = ?"
        )->execute([$id]);

        Session::flash('success', 'MNP routine marked as completed.');
        $this->redirect('/programs/mns?tab=mnp&year=' . $year);
    }

    public function mnsLnsSqComplete(int $id): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/mns?tab=lnssq'); }
        $this->validateCsrf();

        $year = (int)($_POST['year'] ?? date('Y'));
        Database::getInstance()->prepare(
            "UPDATE lns_sq_records SET completed_routine = 1 WHERE id = ?"
        )->execute([$id]);

        Session::flash('success', 'LNS-SQ routine marked as completed.');
        $this->redirect('/programs/mns?tab=lnssq&year=' . $year);
    }

    public function mnsDefaulterReason(): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/mns?tab=defaulter'); }
        $this->validateCsrf();

        $bid        = (int)($_POST['beneficiary_id'] ?? 0);
        $supplement = $_POST['supplement'] ?? '';
        $reason     = trim($_POST['reason'] ?? '');
        $year       = (int)($_POST['year'] ?? date('Y'));

        if ($bid && $supplement && $reason) {
            Database::getInstance()->prepare(
                "INSERT INTO mns_defaulter_reasons (beneficiary_id, year, supplement, reason, noted_by)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE reason = VALUES(reason), noted_by = VALUES(noted_by), noted_at = NOW()"
            )->execute([$bid, $year, $supplement, $reason, \Core\Session::get('user_id')]);
            Session::flash('success', 'Reason saved.');
        }

        $this->redirect('/programs/mns?tab=defaulter&year=' . $year);
    }

    public function generic(string $code): void
    {
        $this->requireAuth();

        $code    = strtoupper($code);
        $program = (new Program())->findByCode($code);

        if (!$program || in_array($code, ['OPT', 'DSP', 'MNS'])) {
            $this->redirect('/dashboard');
        }

        $year     = (int)($_GET['year']     ?? date('Y'));
        $barangay = trim($_GET['barangay']  ?? '');

        $active      = $this->enrollmentModel->getFiltered($code, $year, $barangay);
        $history     = $this->enrollmentModel->getHistory($code, $year);
        $stats       = $this->enrollmentModel->getStats($code);
        $notEnrolled = $this->enrollmentModel->getNotEnrolledInProgram($code);
        $barangays   = (new \App\Models\Beneficiary())->getAllBarangays();

        $this->view('programs/generic', [
            'program'     => $program,
            'active'      => $active,
            'history'     => $history,
            'stats'       => $stats,
            'notEnrolled' => $notEnrolled,
            'barangays'   => $barangays,
            'year'        => $year,
            'barangay'    => $barangay,
        ]);
    }

    public function genericEnroll(string $code): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/' . strtolower($code)); }
        $this->validateCsrf();

        $code          = strtoupper($code);
        $beneficiaryId = (int)($_POST['beneficiary_id'] ?? 0);
        $year          = (int)($_POST['cycle_year'] ?? date('Y'));

        $result = $this->enrollmentModel->enrollBeneficiary($beneficiaryId, $code, [
            'cycle_year'  => $year,
            'notes'       => trim($_POST['notes'] ?? ''),
            'enrolled_by' => Session::get('user_id'),
        ]);

        Session::flash($result ? 'success' : 'warning',
            $result ? "Enrolled in $code." : "Already enrolled in $code.");
        $this->redirect('/programs/' . strtolower($code));
    }

    public function genericDischarge(string $code): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/' . strtolower($code)); }
        $this->validateCsrf();

        $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
        $action       = $_POST['action'] ?? 'complete';

        if ($action === 'drop') {
            $this->enrollmentModel->drop($enrollmentId);
            Session::flash('success', 'Enrollment dropped.');
        } else {
            $this->enrollmentModel->complete($enrollmentId);
            Session::flash('success', 'Enrollment completed.');
        }

        $this->redirect('/programs/' . strtolower($code));
    }

    public function genericUpdate(string $code): void
    {
        $this->requireAuth();
        $this->requirePermission('programs');
        if (!$this->isPost()) { $this->redirect('/programs/' . strtolower($code)); }
        $this->validateCsrf();

        $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
        $notes        = trim($_POST['notes'] ?? '');
        $year         = !empty($_POST['cycle_year']) ? (int)$_POST['cycle_year'] : null;

        $data = ['notes' => $notes];
        if ($year) $data['cycle_year'] = $year;

        if ($enrollmentId) {
            $this->enrollmentModel->update($enrollmentId, $data);
            Session::flash('success', 'Enrollment updated.');
        }

        $this->redirect('/programs/' . strtolower($code));
    }

    public function genericExport(string $code): void
    {
        $this->requireAuth();

        $code    = strtoupper($code);
        $program = (new Program())->findByCode($code);
        if (!$program || in_array($code, ['OPT', 'DSP', 'MNS'])) {
            $this->redirect('/dashboard');
        }

        $year     = (int)($_GET['year']    ?? date('Y'));
        $barangay = trim($_GET['barangay'] ?? '');
        $active   = $this->enrollmentModel->getFiltered($code, $year, $barangay);
        $history  = $this->enrollmentModel->getHistory($code, $year);
        $all      = array_merge($active, $history);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($program['name'], 0, 31));

        $headers  = ['#', 'Last Name', 'First Name', 'Barangay', 'Sex', 'Date of Birth', 'Enrolled', 'Year', 'Status', 'End Date', 'Notes'];
        $colCount = count($headers);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        // Header row
        foreach ($headers as $i => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $cell      = $colLetter . '1';
            $sheet->setCellValue($cell, $h);
            $style = $sheet->getStyle($cell);
            $style->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
            $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E40AF');
            $style->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)->setWrapText(true);
            $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('FFFFFF');
        }
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Data rows
        foreach ($all as $ri => $row) {
            $excelRow = $ri + 2;
            $isEven   = $ri % 2 === 0;
            $cells    = [
                $ri + 1,
                $row['last_name'],
                $row['first_name'],
                $row['barangay'],
                $row['sex'],
                $row['date_of_birth'] ? date('F j, Y', strtotime($row['date_of_birth'])) : '',
                $row['enrollment_date'] ? date('F j, Y', strtotime($row['enrollment_date'])) : '',
                $row['cycle_year'],
                $row['status'],
                !empty($row['end_date']) ? date('F j, Y', strtotime($row['end_date'])) : '',
                $row['notes'] ?? '',
            ];
            foreach ($cells as $ci => $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
                $cell      = $colLetter . $excelRow;
                $sheet->setCellValue($cell, $val);
                $style = $sheet->getStyle($cell);
                $style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($isEven ? 'F0F4FF' : 'FFFFFF');
                $style->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
                $style->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }
            $sheet->getRowDimension($excelRow)->setRowHeight(18);
        }

        foreach (range(1, $colCount) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastCol}1");

        $filename = $code . '_Enrollment_' . $year . ($barangay ? '_' . $barangay : '');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}.xlsx\"");
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }
}
