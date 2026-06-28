<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use App\Models\Assessment;
use App\Models\Beneficiary;
use App\Models\ProgramEnrollment;

class AssessmentController extends Controller
{
    private Assessment $model;

    public function __construct()
    {
        $this->model = new Assessment();
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->requirePermission('assessments');

        $bid              = (int)($_GET['bid'] ?? $_POST['beneficiary_id'] ?? 0);
        $beneficiaryModel = new Beneficiary();
        $beneficiary      = $bid ? $beneficiaryModel->findById($bid) : null;

        if ($this->isPost()) {
            $this->validateCsrf();

            $bid         = (int)($_POST['beneficiary_id'] ?? 0);
            $beneficiary = $beneficiaryModel->findById($bid);

            if (!$beneficiary) {
                Session::flash('error', 'Beneficiary not found.');
                $this->redirect('/assessments/create');
                return;
            }

            $assessmentDate = trim($_POST['assessment_date'] ?? date('Y-m-d'));
            $ageMonths      = \DateHelper::ageInMonths($beneficiary['date_of_birth'], $assessmentDate);
            $period         = (int)date('n', strtotime($assessmentDate)) <= 6 ? 'January' : 'July';
            $year           = (int)date('Y', strtotime($assessmentDate));

            $weightKg = (float)($_POST['weight_kg'] ?? 0);
            if ($weightKg <= 0) {
                Session::flash('error', 'Weight is required.');
                $this->view('assessments/create', ['beneficiary' => $beneficiary]);
                return;
            }

            // Check for duplicate assessment this period/year
            $dupStmt = Database::getInstance()->prepare(
                "SELECT assessment_date FROM assessments WHERE beneficiary_id = ? AND period = ? AND assessment_year = ?"
            );
            $dupStmt->execute([$bid, $period, $year]);
            $hasDuplicate = $dupStmt->fetch();

            $assessmentId = $this->model->createWithZScore([
                'beneficiary_id'  => $bid,
                'assessment_date' => $assessmentDate,
                'age_in_months'   => $ageMonths,
                'weight_kg'       => $weightKg,
                'height_cm'       => !empty($_POST['height_cm']) ? (float)$_POST['height_cm'] : null,
                'muac_cm'         => !empty($_POST['muac_cm'])   ? (float)$_POST['muac_cm']   : null,
                'sex'             => $beneficiary['sex'],
                'period'          => $period,
                'assessment_year' => $year,
                'assessed_by'     => trim($_POST['assessed_by'] ?? ''),
                'remarks'         => trim($_POST['remarks'] ?? ''),
                'created_by'      => Session::get('user_id'),
            ]);

            $enrollmentModel = new ProgramEnrollment();
            $dspEnrollmentId = $enrollmentModel->autoEnrollDSP($assessmentId);

            $saved = $this->model->findById($assessmentId);
            $parts = [sprintf('WFA: %s (Z=%s)', $saved['nutritional_status'], number_format($saved['weight_for_age_zscore'] ?? 0, 2))];
            if (!empty($saved['hfa_status']))  $parts[] = sprintf('HFA: %s (Z=%s)', $saved['hfa_status'],  number_format($saved['height_for_age_zscore'] ?? 0, 2));
            if (!empty($saved['wflh_status'])) $parts[] = sprintf('WFL/H: %s (Z=%s)', $saved['wflh_status'], number_format($saved['wflh_zscore'] ?? 0, 2));

            \ActivityLog::log('assessment_create', "Assessment recorded for beneficiary ID $bid ({$saved['nutritional_status']})");

            $flashMsg = 'Assessment recorded. ' . implode(' | ', $parts);
            if ($dspEnrollmentId) {
                $wflh = $saved['wflh_status'] ?? '';
                $wfa  = $saved['nutritional_status'] ?? '';
                if ($wflh === 'SW')                   $intervention = 'RUTF';
                elseif ($wflh === 'MW')               $intervention = 'RUSF';
                else                                  $intervention = 'Health Education';
                $flashMsg .= ' — <strong>Auto-enrolled in DSP</strong> (' . $intervention . ').';
            } else {
                // Check if child is currently in DSP and has now recovered
                $activeDsp = $enrollmentModel->getActiveDSPEnrollment($bid);
                if ($activeDsp) {
                    $newWfa  = $saved['nutritional_status'] ?? '';
                    $newWflh = $saved['wflh_status'] ?? '';
                    if (!in_array($newWfa, ['SUW', 'UW']) && !in_array($newWflh, ['SW', 'MW'])) {
                        $flashMsg .= ' — <strong class="text-success">This child\'s nutritional status is now Normal. '
                            . 'Consider discharging them from DSP.</strong>';
                    }
                }
            }
            if ($hasDuplicate) {
                $flashMsg .= ' <strong>Note:</strong> A previous assessment for this period already existed.';
            }
            Session::flash('success', $flashMsg);
            $this->redirect("/beneficiaries/{$bid}");
        }

        $lastAssessment     = null;
        $existingThisPeriod = null;
        if ($beneficiary) {
            $db    = Database::getInstance();
            $stmt  = $db->prepare("SELECT * FROM assessments WHERE beneficiary_id = ? ORDER BY assessment_date DESC LIMIT 1");
            $stmt->execute([$bid]);
            $lastAssessment = $stmt->fetch() ?: null;

            $curPeriod = (int)date('n') <= 6 ? 'January' : 'July';
            $stmt2     = $db->prepare("SELECT assessment_date FROM assessments WHERE beneficiary_id = ? AND period = ? AND assessment_year = ?");
            $stmt2->execute([$bid, $curPeriod, (int)date('Y')]);
            $existingThisPeriod = $stmt2->fetch() ?: null;
        }

        $allBeneficiaries = $beneficiary ? [] : $beneficiaryModel->search('', '', 1, 99999)['rows'];

        $alreadyAssessedIds = [];
        if (!$beneficiary) {
            $curPeriod2 = (int)date('n') <= 6 ? 'January' : 'July';
            $stmt3 = Database::getInstance()->prepare(
                "SELECT DISTINCT beneficiary_id FROM assessments WHERE period = ? AND assessment_year = ?"
            );
            $stmt3->execute([$curPeriod2, (int)date('Y')]);
            $alreadyAssessedIds = array_fill_keys(array_column($stmt3->fetchAll(), 'beneficiary_id'), true);
        }

        $this->view('assessments/create', [
            'beneficiary'        => $beneficiary,
            'allBeneficiaries'   => $allBeneficiaries,
            'alreadyAssessedIds' => $alreadyAssessedIds,
            'lastAssessment'     => $lastAssessment,
            'existingThisPeriod' => $existingThisPeriod,
        ]);
    }

    public function batch(): void
    {
        $this->requireAuth();
        $this->requirePermission('assessments');

        $beneficiaryModel = new Beneficiary();
        $role             = Session::get('user_role');

        $selectedBarangay = trim($_GET['barangay'] ?? $_POST['barangay'] ?? '');
        $assessmentDate   = trim($_GET['assessment_date'] ?? $_POST['assessment_date'] ?? date('Y-m-d'));

        // BHW can only see their barangay
        if ($role === 'bhw') {
            $selectedBarangay = Session::get('user_barangay', '');
        }

        $barangays        = $beneficiaryModel->getAllBarangays();
        $allBeneficiaries = $selectedBarangay !== '' ? $beneficiaryModel->findByBarangay($selectedBarangay) : [];

        $showAll        = isset($_GET['show_all']);
        $datePeriod     = (int)date('n', strtotime($assessmentDate)) <= 6 ? 'January' : 'July';
        $dateYear       = (int)date('Y', strtotime($assessmentDate));
        $alreadyWeighedMap = [];

        if (!empty($allBeneficiaries)) {
            $stmt = Database::getInstance()->prepare(
                "SELECT DISTINCT beneficiary_id FROM assessments WHERE period = ? AND assessment_year = ?"
            );
            $stmt->execute([$datePeriod, $dateYear]);
            $alreadyWeighedMap = array_fill_keys(array_column($stmt->fetchAll(), 'beneficiary_id'), true);
        }

        $beneficiaries = $showAll
            ? $allBeneficiaries
            : array_values(array_filter($allBeneficiaries, fn($b) => !isset($alreadyWeighedMap[$b['id']])));

        if ($this->isPost() && !empty($_POST['entries'])) {
            $this->validateCsrf();

            $date        = trim($_POST['assessment_date'] ?? date('Y-m-d'));
            $period      = (int)date('n', strtotime($date)) <= 6 ? 'January' : 'July';
            $year        = (int)date('Y', strtotime($date));
            $saved       = 0;
            $skipped     = 0;
            $dspEnrolled = 0;

            $assessedBy = trim($_POST['assessed_by'] ?? Session::get('user_name', ''));

            foreach ($_POST['entries'] as $entry) {
                $bid      = (int)($entry['beneficiary_id'] ?? 0);
                $weightKg = (float)($entry['weight_kg'] ?? 0);

                if ($weightKg <= 0 || !$bid) { $skipped++; continue; }

                $b = $beneficiaryModel->findById($bid);
                if (!$b) { $skipped++; continue; }

                $assessmentId = $this->model->createWithZScore([
                    'beneficiary_id'  => $bid,
                    'assessment_date' => $date,
                    'age_in_months'   => \DateHelper::ageInMonths($b['date_of_birth'], $date),
                    'weight_kg'       => $weightKg,
                    'height_cm'       => !empty($entry['height_cm']) ? (float)$entry['height_cm'] : null,
                    'muac_cm'         => !empty($entry['muac_cm'])   ? (float)$entry['muac_cm']   : null,
                    'sex'             => $b['sex'],
                    'period'          => $period,
                    'assessment_year' => $year,
                    'assessed_by'     => $assessedBy,
                    'created_by'      => Session::get('user_id'),
                ]);

                if ((new ProgramEnrollment())->autoEnrollDSP($assessmentId)) $dspEnrolled++;
                $saved++;
            }

            \ActivityLog::log('batch_assessment', "Batch assessment: $saved saved, $skipped skipped — barangay: $selectedBarangay");
            $flashMsg = "$saved assessment" . ($saved !== 1 ? 's' : '') . " saved, $skipped skipped (no weight entered).";
            if ($dspEnrolled > 0) {
                $flashMsg .= " <strong>$dspEnrolled " . ($dspEnrolled !== 1 ? 'children were' : 'child was') . " auto-enrolled in DSP.</strong>";
            }
            Session::flash('success', $flashMsg);
            $this->redirect('/beneficiaries');
        }

        $this->view('assessments/batch', [
            'barangays'         => $barangays,
            'beneficiaries'     => $beneficiaries,
            'selectedBarangay'  => $selectedBarangay,
            'assessmentDate'    => $assessmentDate,
            'alreadyWeighedMap' => $alreadyWeighedMap,
            'showAll'           => $showAll,
            'totalInBarangay'   => count($allBeneficiaries),
        ]);
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->requirePermission('assessments');
        if (!$this->isPost()) { $this->redirect('/beneficiaries'); }
        $this->validateCsrf();

        $assessment = $this->model->findById((int)$id);
        if (!$assessment) {
            $this->redirect('/beneficiaries');
        }

        $bid = $assessment['beneficiary_id'];
        $this->model->delete((int)$id);
        \ActivityLog::log('assessment_delete', "Deleted assessment ID $id for beneficiary ID $bid");
        Session::flash('success', 'Assessment deleted.');
        $this->redirect("/beneficiaries/{$bid}");
    }
}
