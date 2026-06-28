<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use App\Models\Assessment;
use App\Models\ProgramEnrollment;
use App\Models\VitaminARecord;

class SeederController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        $this->view('admin/seeder', []);
    }

    public function run(): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/admin/seed'); }
        $this->validateCsrf();

        $db = Database::getInstance();

        // Check if already seeded
        $count = (int)$db->query("SELECT COUNT(*) FROM beneficiaries WHERE source = 'Demo'")->fetchColumn();
        if ($count > 0) {
            Session::flash('error', 'Demo data already exists. Clear it first before re-seeding.');
            $this->redirect('/admin/seed');
            return;
        }

        $barangays = ['Barangay Mabuhay', 'Barangay Masagana', 'Barangay Maliwanag'];

        $lastNames  = ['Santos','Reyes','Cruz','Bautista','Ocampo','Garcia','Mendoza','Torres','Flores','Villanueva','Ramos','Pascual','Navarro','Aquino','Dela Cruz'];
        $firstNames = ['Maria','Jose','Ana','Juan','Rosa','Carlos','Elena','Miguel','Lourdes','Ramon','Cristina','Antonio','Marites','Rodrigo','Leonora'];
        $mothers    = ['Rosario Santos','Lilia Reyes','Cora Cruz','Nenita Bautista','Mila Ocampo','Gina Garcia','Tess Mendoza','Nena Torres','Perla Flores','Alma Villanueva'];
        $fathers    = ['Roberto Santos','Eduardo Reyes','Mario Cruz','Ernesto Bautista','Danilo Ocampo','Felix Garcia','Rolando Mendoza','Celso Torres','Bernardo Flores','Alfredo Villanueva'];

        $assessmentModel  = new Assessment();
        $enrollmentModel  = new ProgramEnrollment();
        $userId           = Session::get('user_id');
        $created          = 0;

        srand(42); // deterministic

        foreach ($barangays as $barangay) {
            $childCount = rand(8, 12);
            for ($i = 0; $i < $childCount; $i++) {
                $sex       = rand(0, 1) ? 'Male' : 'Female';
                $ageMonths = rand(0, 59);
                $dob       = date('Y-m-d', strtotime("-{$ageMonths} months"));
                $lastName  = $lastNames[array_rand($lastNames)];
                $firstName = $firstNames[array_rand($firstNames)];

                // Avoid exact duplicates
                $stmt = $db->prepare("SELECT COUNT(*) FROM beneficiaries WHERE last_name=? AND first_name=? AND date_of_birth=? AND barangay=?");
                $stmt->execute([$lastName, $firstName, $dob, $barangay]);
                if ((int)$stmt->fetchColumn() > 0) continue;

                $stmt = $db->prepare(
                    "INSERT INTO beneficiaries
                     (last_name, first_name, date_of_birth, sex, barangay,
                      purok_zone, household_no, mother_name, father_name,
                      contact_number, income_classification, philhealth_status,
                      is_4ps_member, source, created_by, created_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,datetime('now'))"
                );
                $stmt->execute([
                    $lastName, $firstName, $dob, $sex, $barangay,
                    'Purok ' . rand(1, 5),
                    sprintf('%03d', rand(1, 99)),
                    $mothers[array_rand($mothers)],
                    $fathers[array_rand($fathers)],
                    '09' . rand(100000000, 999999999),
                    ['Poor','Near Poor','Non-Poor'][rand(0,2)],
                    ['Member','Indigent','Non-member'][rand(0,2)],
                    rand(0,1),
                    'Demo',
                    $userId,
                ]);
                $beneficiaryId = (int)$db->lastInsertId();

                // Add 1–3 assessments over the past year
                $numAssessments = rand(1, 3);
                $prevDate = null;
                for ($j = 0; $j < $numAssessments; $j++) {
                    $daysBack      = ($numAssessments - $j) * rand(60, 120);
                    $assessDate    = date('Y-m-d', strtotime("-{$daysBack} days"));
                    $assessMonths  = \DateHelper::ageInMonths($dob, $assessDate);
                    if ($assessMonths < 0 || $assessMonths > 59) continue;
                    $period = (int)date('n', strtotime($assessDate)) <= 6 ? 'January' : 'July';
                    $year   = (int)date('Y', strtotime($assessDate));

                    // Realistic weight based on WHO medians
                    if ($assessMonths <= 6) {
                        $baseWeight = 3.3 + $assessMonths * 0.77;
                    } elseif ($assessMonths <= 12) {
                        $baseWeight = 7.9 + ($assessMonths - 6) * 0.28;
                    } else {
                        $baseWeight = 9.6 + ($assessMonths - 12) * 0.22;
                    }
                    // ~30% chance malnourished (for demo variety), rest normal variation
                    $modifier = (rand(0, 100) < 30) ? rand(-30, -15) / 10 : rand(-8, 8) / 10;
                    $weight   = round(max(2.5, $baseWeight + $modifier), 1);

                    // Realistic height based on WHO medians
                    $baseHeight = $assessMonths <= 12
                        ? 50 + $assessMonths * 2.2
                        : 76.4 + ($assessMonths - 12) * 0.9;
                    $height = $assessMonths > 0 ? round($baseHeight + rand(-20, 20) / 10, 1) : null;

                    $assessmentId = $assessmentModel->createWithZScore([
                        'beneficiary_id'  => $beneficiaryId,
                        'assessment_date' => $assessDate,
                        'age_in_months'   => $assessMonths,
                        'weight_kg'       => $weight,
                        'height_cm'       => $height,
                        'muac_cm'         => $assessMonths >= 6 ? round(10 + rand(0, 50) / 10, 1) : null,
                        'sex'             => $sex,
                        'period'          => $period,
                        'assessment_year' => $year,
                        'assessed_by'     => 'Demo Seeder',
                        'created_by'      => $userId,
                    ]);

                    $enrollmentModel->autoEnrollDSP($assessmentId);
                }

                // Vitamin A — seed both rounds if current month >= August, else just February
                $currentMonth = (int)date('n');
                $currentYear  = (int)date('Y');
                $vitaRounds   = $currentMonth >= 8
                    ? [['date' => date('Y-02-15'), 'round' => 'February'], ['date' => date('Y-08-15'), 'round' => 'August']]
                    : [['date' => date('Y-02-15'), 'round' => 'February']];
                foreach ($vitaRounds as $vitaRound) {
                    if (!rand(0, 1)) continue;
                    $vitaDate  = $vitaRound['date'];
                    $ageAtVita = \DateHelper::ageInMonths($dob, $vitaDate);
                    if ($ageAtVita >= 6 && $ageAtVita <= 59) {
                        $db->prepare(
                            "INSERT INTO vitamin_a_records
                             (beneficiary_id, distribution_date, round, year, dosage_iu, capsule_color, administered_by, created_by)
                             VALUES (?,?,?,?,?,?,?,?)"
                        )->execute([
                            $beneficiaryId,
                            $vitaDate,
                            $vitaRound['round'],
                            $currentYear,
                            $ageAtVita < 12 ? 100000 : 200000,
                            $ageAtVita < 12 ? 'Blue' : 'Red',
                            'Demo Seeder',
                            $userId,
                        ]);
                    }
                }

                // MNP — use current date so it shows in the current month
                if (rand(0, 1)) {
                    $mnpDate  = date('Y-m-d');
                    $ageAtMnp = \DateHelper::ageInMonths($dob, $mnpDate);
                    if ($ageAtMnp >= 6 && $ageAtMnp <= 59) {
                        $ageGroup = $ageAtMnp <= 11 ? '6-11 months' : ($ageAtMnp <= 23 ? '12-23 months' : '24-59 months');
                        $db->prepare(
                            "INSERT INTO mnp_records
                             (beneficiary_id, date_given, year, age_group, completed_routine, given_by)
                             VALUES (?,?,?,?,?,?)"
                        )->execute([
                            $beneficiaryId,
                            $mnpDate,
                            $currentYear,
                            $ageGroup,
                            rand(0, 1),
                            $userId,
                        ]);
                    }
                }

                // LNS-SQ — use current date so it shows in the current month
                if (rand(0, 1)) {
                    $lnsDate  = date('Y-m-d');
                    $ageAtLns = \DateHelper::ageInMonths($dob, $lnsDate);
                    if ($ageAtLns >= 6 && $ageAtLns <= 23) {
                        $ageGroup = $ageAtLns <= 11 ? '6-11 months' : '12-23 months';
                        $db->prepare(
                            "INSERT INTO lns_sq_records
                             (beneficiary_id, date_given, year, age_group, completed_routine, given_by)
                             VALUES (?,?,?,?,?,?)"
                        )->execute([
                            $beneficiaryId,
                            $lnsDate,
                            $currentYear,
                            $ageGroup,
                            rand(0, 1),
                            $userId,
                        ]);
                    }
                }

                $created++;
            }
        }

        \ActivityLog::log('demo_seed', "Seeded $created demo beneficiaries across " . count($barangays) . " barangays");
        Session::flash('success', "Demo data seeded: <strong>$created beneficiaries</strong> created across " . count($barangays) . " barangays with assessments and program enrollments.");
        $this->redirect('/admin/seed');
    }

    public function clear(): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/admin/seed'); }
        $this->validateCsrf();

        $db = Database::getInstance();

        // Get demo beneficiary IDs
        $ids = $db->query("SELECT id FROM beneficiaries WHERE source = 'Demo'")->fetchAll(\PDO::FETCH_COLUMN);
        if (empty($ids)) {
            Session::flash('info', 'No demo data found.');
            $this->redirect('/admin/seed');
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db->prepare("DELETE FROM assessments            WHERE beneficiary_id IN ($placeholders)")->execute($ids);
        $db->prepare("DELETE FROM program_enrollments    WHERE beneficiary_id IN ($placeholders)")->execute($ids);
        $db->prepare("DELETE FROM vitamin_a_records      WHERE beneficiary_id IN ($placeholders)")->execute($ids);
        $db->prepare("DELETE FROM mnp_records            WHERE beneficiary_id IN ($placeholders)")->execute($ids);
        $db->prepare("DELETE FROM lns_sq_records         WHERE beneficiary_id IN ($placeholders)")->execute($ids);
        $db->prepare("DELETE FROM dispensing_records     WHERE beneficiary_id IN ($placeholders)")->execute($ids);
        $db->prepare("DELETE FROM beneficiaries          WHERE id             IN ($placeholders)")->execute($ids);

        // Reset auto-increment if no beneficiaries remain
        $remaining = (int)$db->query("SELECT COUNT(*) FROM beneficiaries")->fetchColumn();
        if ($remaining === 0) {
            $db->exec("DELETE FROM sqlite_sequence WHERE name IN ('beneficiaries','assessments','program_enrollments','vitamin_a_records','mnp_records','lns_sq_records','dispensing_records')");
        }

        \ActivityLog::log('demo_clear', 'Cleared ' . count($ids) . ' demo beneficiaries and related records');
        Session::flash('success', count($ids) . ' demo beneficiaries and all related records have been deleted.');
        $this->redirect('/admin/seed');
    }
}
