<?php

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Core\Session;
use App\Models\Program;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $db   = Database::getInstance();
        $role = Session::get('user_role');
        $bar  = in_array($role, ['bhw', 'bns']) ? Session::get('user_barangay', '') : '';

        // Helper to build WHERE snippets with optional barangay filter
        $bWhere  = $bar ? ' AND barangay = ?' : '';
        $bParams = $bar ? [$bar] : [];

        $stmt = $db->prepare("SELECT COUNT(*) FROM beneficiaries WHERE deleted_at IS NULL$bWhere");
        $stmt->execute($bParams);
        $totalBeneficiaries = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT a.beneficiary_id)
             FROM assessments a JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE a.assessment_year = YEAR(NOW())
               AND b.deleted_at IS NULL" . ($bar ? ' AND b.barangay = ?' : '')
        );
        $stmt->execute($bParams);
        $activeOpt = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM program_enrollments pe
             JOIN beneficiaries b ON b.id = pe.beneficiary_id
             WHERE pe.program = 'DSP' AND pe.status = 'Active'" . ($bar ? ' AND b.barangay = ?' : '')
        );
        $stmt->execute($bParams);
        $activeDsp = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT v.beneficiary_id)
             FROM vitamin_a_records v JOIN beneficiaries b ON b.id = v.beneficiary_id
             WHERE v.year = YEAR(NOW())" . ($bar ? ' AND b.barangay = ?' : '')
        );
        $stmt->execute($bParams);
        $mnsCoverage = (int) $stmt->fetchColumn();

        // For-follow-up count (worsened between last two assessments)
        // Beneficiaries not yet assessed in the current OPT period
        $currentMonth  = (int)date('n');
        $currentYear   = (int)date('Y');
        $periodLabel   = $currentMonth <= 6 ? 'January' : 'July';
        $cutoffDob     = date('Y-m-d', strtotime('-59 months'));

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM beneficiaries b
             WHERE b.deleted_at IS NULL
               AND b.date_of_birth >= '$cutoffDob'"
            . ($bar ? ' AND b.barangay = ?' : '') .
            " AND NOT EXISTS (
                SELECT 1 FROM assessments a
                WHERE a.beneficiary_id = b.id
                  AND a.assessment_year = $currentYear
                  AND a.period = '$periodLabel'
             )"
        );
        $stmt->execute($bParams);
        $notAssessedCount = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT b.id)
             FROM beneficiaries b
             JOIN assessments a1 ON a1.id = (
                 SELECT id FROM assessments WHERE beneficiary_id = b.id ORDER BY assessment_date DESC LIMIT 1
             )
             JOIN assessments a2 ON a2.id = (
                 SELECT id FROM assessments WHERE beneficiary_id = b.id ORDER BY assessment_date DESC LIMIT 1 OFFSET 1
             )
             WHERE b.deleted_at IS NULL
               AND CASE a1.nutritional_status WHEN 'SUW' THEN 1 WHEN 'UW' THEN 2 WHEN 'Normal' THEN 3 WHEN 'OW' THEN 4 WHEN 'OB' THEN 5 ELSE 0 END
                 < CASE a2.nutritional_status WHEN 'SUW' THEN 1 WHEN 'UW' THEN 2 WHEN 'Normal' THEN 3 WHEN 'OW' THEN 4 WHEN 'OB' THEN 5 ELSE 0 END" .
            ($bar ? ' AND b.barangay = ?' : '')
        );
        $stmt->execute($bParams);
        $followupCount = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT b.barangay, a.nutritional_status, COUNT(*) as count
             FROM assessments a JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE a.assessment_year = YEAR(NOW())
               AND b.deleted_at IS NULL" . ($bar ? ' AND b.barangay = ?' : '') . "
             GROUP BY b.barangay, a.nutritional_status
             ORDER BY b.barangay"
        );
        $stmt->execute($bParams);
        $statusData = $stmt->fetchAll();

        $stmt = $db->prepare(
            "SELECT a.assessment_year, a.period, a.nutritional_status, COUNT(*) as count
             FROM assessments a
             JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE a.assessment_year >= YEAR(NOW()) - 2
               AND b.deleted_at IS NULL
             GROUP BY a.assessment_year, a.period, a.nutritional_status
             ORDER BY a.assessment_year, a.period"
        );
        $stmt->execute();
        $trendData = $stmt->fetchAll();

        try {
            $stmt = $db->prepare(
                "SELECT p.name, p.code, p.color, p.icon, COUNT(pe.id) as count
                 FROM programs p
                 LEFT JOIN program_enrollments pe ON pe.program = p.code
                     AND pe.status = 'Active'"
                . ($bar ? " AND pe.beneficiary_id IN (SELECT id FROM beneficiaries WHERE barangay = ? AND deleted_at IS NULL)" : "")
                . " WHERE p.is_active = 1
                 GROUP BY p.id, p.name, p.code, p.color, p.icon
                 ORDER BY p.sort_order, p.name"
            );
            $stmt->execute($bParams);
            $enrollmentBreakdown = $stmt->fetchAll();
        } catch (\Throwable $e) {
            // Fallback if programs table doesn't exist yet
            $stmt = $db->prepare(
                "SELECT pe.program AS code, pe.program AS name, 'primary' AS color,
                        'bi-clipboard-check' AS icon, COUNT(*) as count
                 FROM program_enrollments pe
                 JOIN beneficiaries b ON b.id = pe.beneficiary_id
                 WHERE pe.status = 'Active' AND b.deleted_at IS NULL"
                . ($bar ? ' AND b.barangay = ?' : '')
                . " GROUP BY pe.program ORDER BY pe.program"
            );
            $stmt->execute($bParams);
            $enrollmentBreakdown = $stmt->fetchAll();
        }

        // Pending validation count (for midwife/admin/nutritionist)
        $pendingValidation = 0;
        if (in_array($role, ['midwife', 'admin', 'nutritionist'])) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM beneficiaries WHERE validation_status = 'pending' AND deleted_at IS NULL");
                $stmt->execute();
                $pendingValidation = (int)$stmt->fetchColumn();
            } catch (\Throwable $e) {}
        }

        // Mobile activity data (admin/nutritionist only)
        $submittedCount              = 0;
        $pendingAssessmentValidation = 0;
        $recentSubmissions           = [];
        if (in_array($role, ['admin', 'nutritionist'])) {
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM beneficiaries WHERE submitted_at IS NOT NULL AND deleted_at IS NULL");
                $stmt->execute();
                $submittedCount = (int)$stmt->fetchColumn();
            } catch (\Throwable $e) {}

            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM assessments WHERE validation_status = 'pending'");
                $stmt->execute();
                $pendingAssessmentValidation = (int)$stmt->fetchColumn();
            } catch (\Throwable $e) {}

            try {
                $stmt = $db->prepare(
                    "SELECT b.id, b.last_name, b.first_name, b.barangay, b.submitted_at,
                            u.full_name AS submitted_by_name
                     FROM beneficiaries b
                     LEFT JOIN users u ON u.id = b.submitted_by
                     WHERE b.submitted_at IS NOT NULL AND b.deleted_at IS NULL
                     ORDER BY b.submitted_at DESC LIMIT 5"
                );
                $stmt->execute();
                $recentSubmissions = $stmt->fetchAll();
            } catch (\Throwable $e) {}
        }

        $this->view('dashboard/index', [
            'totalBeneficiaries'          => $totalBeneficiaries,
            'activeOpt'                   => $activeOpt,
            'activeDsp'                   => $activeDsp,
            'mnsCoverage'                 => $mnsCoverage,
            'followupCount'               => $followupCount,
            'notAssessedCount'            => $notAssessedCount,
            'periodLabel'                 => $periodLabel,
            'currentYear'                 => $currentYear,
            'statusData'                  => $statusData,
            'trendData'                   => $trendData,
            'enrollmentBreakdown'         => $enrollmentBreakdown,
            'pendingValidation'           => $pendingValidation,
            'submittedCount'              => $submittedCount,
            'pendingAssessmentValidation' => $pendingAssessmentValidation,
            'recentSubmissions'           => $recentSubmissions,
        ]);
    }
}
