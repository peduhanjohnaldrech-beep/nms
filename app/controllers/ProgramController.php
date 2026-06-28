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

        $year   = (int)($_GET['year'] ?? date('Y'));
        $period = $_GET['period'] ?? '';

        $statusData = (new Assessment())->getNutritionalStatus($year, $period);

        $db     = Database::getInstance();
        $params = [$year];
        $where  = 'a.assessment_year = ?';

        if ($period) { $where .= ' AND a.period = ?'; $params[] = $period; }
        if (Session::get('user_role') === 'bhw') { $where .= ' AND b.barangay = ?'; $params[] = Session::get('user_barangay'); }

        $stmt = $db->prepare(
            "SELECT a.*, b.last_name, b.first_name, b.middle_name, b.barangay, b.sex, b.date_of_birth
             FROM assessments a JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL AND $where ORDER BY b.barangay, a.nutritional_status, b.last_name"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Eligible: children who were 0–59 months at any point during the selected year
        $eligibleParams = ["{$year}-12-31", "{$year}-01-01"];
        $eligibleWhere  = "b.deleted_at IS NULL
            AND b.date_of_birth <= ?
            AND b.date_of_birth >= date(?, '-59 months')";
        if (Session::get('user_role') === 'bhw') {
            $eligibleWhere .= ' AND b.barangay = ?';
            $eligibleParams[] = Session::get('user_barangay');
        }

        $eligStmt = $db->prepare("SELECT COUNT(*) FROM beneficiaries b WHERE $eligibleWhere");
        $eligStmt->execute($eligibleParams);
        $totalEligible = (int)$eligStmt->fetchColumn();

   // Not yet weighed: eligible but no assessment this period/year
$nywParams = $eligibleParams;   // bind $eligibleWhere's placeholders first (DOB range, + barangay if bhw)
$nywParams[] = $year;
$periodClause = '';
if ($period) {
    $periodClause = ' AND a.period = ?';
    $nywParams[] = $period;
}
$nywStmt = $db->prepare(
    "SELECT b.id, b.last_name, b.first_name, b.barangay, b.sex, b.date_of_birth,
            (CAST(strftime('%Y','now') AS INTEGER) - CAST(strftime('%Y', b.date_of_birth) AS INTEGER)) * 12
            + (CAST(strftime('%m','now') AS INTEGER) - CAST(strftime('%m', b.date_of_birth) AS INTEGER))
            + CASE WHEN strftime('%d','now') < strftime('%d', b.date_of_birth) THEN -1 ELSE 0 END AS age_months
     FROM beneficiaries b
     WHERE $eligibleWhere
     AND b.id NOT IN (
         SELECT a.beneficiary_id FROM assessments a WHERE a.assessment_year = ?$periodClause
     )
     ORDER BY b.barangay, b.last_name"
);
$nywStmt->execute($nywParams);
$notYetWeighed = $nywStmt->fetchAll();
        $this->view('programs/opt', [
            'rows'          => $rows,
            'statusData'    => $statusData,
            'year'          => $year,
            'period'        => $period,
            'totalEligible' => $totalEligible,
            'notYetWeighed' => $notYetWeighed,
        ]);
    }

    public function dsp(): void
    {
        $this->requireAuth();

        $eligible         = $this->enrollmentModel->getEligibleForDSP();
        $active           = $this->enrollmentModel->getActive('DSP');
        $notEnrolled      = $this->enrollmentModel->getNotEnrolledInProgram('DSP');
        $readyToDischarge = $this->enrollmentModel->getReadyToDischarge();
        $historyYear      = (int)($_GET['history_year'] ?? date('Y'));
        $history          = $this->enrollmentModel->getHistory('DSP', $historyYear);

        $this->view('programs/dsp', [
            'eligible'         => $eligible,
            'active'           => $active,
            'notEnrolled'      => $notEnrolled,
            'readyToDischarge' => $readyToDischarge,
            'history'          => $history,
            'historyYear'      => $historyYear,
        ]);
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

        $round = $_GET['round'] ?? 'February';
        $year  = (int)($_GET['year'] ?? date('Y'));
        $tab   = $_GET['tab'] ?? 'vitaminA';

        $mnpModel        = new MnpRecord();
        $lnsModel        = new LnsSqRecord();

        $vitaModel       = new VitaminARecord();
        $eligible        = $this->enrollmentModel->getEligibleForMNS($round, $year);
        $vitaRecords     = $vitaModel->getAllByRound($round, $year);
        $vitaCoverage    = $vitaModel->getCoverageByBarangay($round, $year);
        $mnpCompletion   = $mnpModel->getCompletionByBarangay($year);
        $lnsCompletion   = $lnsModel->getCompletionByBarangay($year);
        $mnpRecords      = $mnpModel->getAllByYear($year);
        $lnsRecords      = $lnsModel->getAllByYear($year);
        $asOfDate        = $year < (int)date('Y') ? "{$year}-12-31" : date('Y-m-d');
        $mnpNotYet       = $mnpModel->getNotYetReceived($year, $asOfDate);
        $lnsNotYet       = $lnsModel->getNotYetReceived($year, $asOfDate);
        $allBeneficiaries = Database::getInstance()->query(
            "SELECT id, last_name, first_name, barangay, date_of_birth FROM beneficiaries
             WHERE deleted_at IS NULL ORDER BY last_name, first_name"
        )->fetchAll();

        $this->view('programs/mns', [
            'eligible'         => $eligible,
            'vitaRecords'      => $vitaRecords,
            'coverage'         => $vitaCoverage,
            'mnpCompletion'    => $mnpCompletion,
            'lnsCompletion'    => $lnsCompletion,
            'mnpRecords'       => $mnpRecords,
            'lnsRecords'       => $lnsRecords,
            'mnpNotYet'        => $mnpNotYet,
            'lnsNotYet'        => $lnsNotYet,
            'allBeneficiaries' => $allBeneficiaries,
            'round'            => $round,
            'year'             => $year,
            'tab'              => $tab,
            'asOfDate'         => $asOfDate,
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
            $round = $_POST['round'] ?? 'February';
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

        $this->redirect('/programs/mns?tab=vitaminA&round=' . urlencode($_POST['round'] ?? 'February') . '&year=' . ($_POST['year'] ?? date('Y')));
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
        $round = $_POST['round'] ?? 'February';
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
                'beneficiary_id'    => $bid,
                'given_by'          => Session::get('user_id'),
                'date_given'        => $date,
                'year'              => $year,
                'age_group'         => $_POST['age_group'] ?? '',
                'completed_routine' => isset($_POST['completed_routine']) ? 1 : 0,
                'notes'             => trim($_POST['notes'] ?? ''),
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
                'beneficiary_id'    => $bid,
                'given_by'          => Session::get('user_id'),
                'date_given'        => $date,
                'year'              => $year,
                'age_group'         => $_POST['age_group'] ?? '',
                'completed_routine' => isset($_POST['completed_routine']) ? 1 : 0,
                'notes'             => trim($_POST['notes'] ?? ''),
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
