<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;

class StatsApiController extends ApiController
{
    /**
     * GET /api/barangays
     * Returns distinct barangay names from the beneficiaries table.
     */
    public function barangays(): void
    {
        $this->requireApiAuth();

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT DISTINCT barangay FROM beneficiaries
             WHERE deleted_at IS NULL AND barangay IS NOT NULL AND barangay != ''
             ORDER BY barangay"
        );
        $stmt->execute();
        $barangays = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $this->success(['barangays' => $barangays]);
    }

    /**
     * GET /api/stats/dashboard
     * Query: ?barangay=
     * Returns the same six stat cards shown on the web dashboard.
     */
    public function dashboard(): void
    {
        $this->requireApiAuth();

        // BHW is restricted to their assigned barangay
        $barangay = $this->isBhw()
            ? $this->userBarangay()
            : ($_GET['barangay'] ?? null);

        $db      = Database::getInstance();
        $bWhere  = $barangay ? ' AND b.barangay = ?' : '';
        $bParams = $barangay ? [$barangay] : [];

        // 1. Total active beneficiaries
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM beneficiaries b
             WHERE b.deleted_at IS NULL$bWhere"
        );
        $stmt->execute($bParams);
        $totalBeneficiaries = (int) $stmt->fetchColumn();

        // 2. OPT assessed (distinct beneficiaries assessed this year)
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT a.beneficiary_id)
             FROM assessments a
             JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE a.assessment_year = YEAR(NOW())
               AND b.deleted_at IS NULL$bWhere"
        );
        $stmt->execute($bParams);
        $optAssessed = (int) $stmt->fetchColumn();

        // 3. DSP active enrollments
        $stmt = $db->prepare(
            "SELECT COUNT(*)
             FROM program_enrollments pe
             JOIN beneficiaries b ON b.id = pe.beneficiary_id
             WHERE pe.program = 'DSP' AND pe.status = 'Active'
               AND b.deleted_at IS NULL$bWhere"
        );
        $stmt->execute($bParams);
        $dspActive = (int) $stmt->fetchColumn();

        // 4. MNS coverage (distinct children who received Vitamin A this year)
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT v.beneficiary_id)
             FROM vitamin_a_records v
             JOIN beneficiaries b ON b.id = v.beneficiary_id
             WHERE v.year = YEAR(NOW())
               AND b.deleted_at IS NULL$bWhere"
        );
        $stmt->execute($bParams);
        $mnsCoverage = (int) $stmt->fetchColumn();

        // 5. For follow-up (nutritional status worsened between last two assessments)
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT b.id)
             FROM beneficiaries b
             JOIN assessments a1 ON a1.id = (
                 SELECT id FROM assessments
                 WHERE beneficiary_id = b.id
                 ORDER BY assessment_date DESC LIMIT 1
             )
             JOIN assessments a2 ON a2.id = (
                 SELECT id FROM assessments
                 WHERE beneficiary_id = b.id
                 ORDER BY assessment_date DESC LIMIT 1 OFFSET 1
             )
             WHERE b.deleted_at IS NULL
               AND CASE a1.nutritional_status
                     WHEN 'SUW'    THEN 1
                     WHEN 'UW'     THEN 2
                     WHEN 'Normal' THEN 3
                     WHEN 'OW'     THEN 4
                     WHEN 'OB'     THEN 5
                     ELSE 0 END
                 < CASE a2.nutritional_status
                     WHEN 'SUW'    THEN 1
                     WHEN 'UW'     THEN 2
                     WHEN 'Normal' THEN 3
                     WHEN 'OW'     THEN 4
                     WHEN 'OB'     THEN 5
                     ELSE 0 END$bWhere"
        );
        $stmt->execute($bParams);
        $forFollowup = (int) $stmt->fetchColumn();

        // 6. Not yet assessed in the current OPT period (children 0-59 months)
        $currentMonth = (int) date('n');
        $currentYear  = (int) date('Y');
        $period       = $currentMonth <= 6 ? 'January' : 'July';
        $cutoffDob    = date('Y-m-d', strtotime('-59 months'));

        $notYetAssessed = 0;
        $stmt = $db->prepare(
            "SELECT COUNT(*)
             FROM beneficiaries b
             WHERE b.deleted_at IS NULL
               AND b.date_of_birth >= ?$bWhere
               AND NOT EXISTS (
                   SELECT 1 FROM assessments a
                   WHERE a.beneficiary_id = b.id
                     AND a.assessment_year = ?
                     AND a.period = ?
               )"
        );
        $stmt->execute([$cutoffDob, ...$bParams, $currentYear, $period]);
        $notYetAssessed = (int) $stmt->fetchColumn();

        // SUW + UW counts (for mobile dashboard cards)
        $stmt = $db->prepare(
            "SELECT a.nutritional_status, COUNT(DISTINCT a.beneficiary_id) as cnt
             FROM assessments a
             JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL
               AND a.id = (
                   SELECT id FROM assessments
                   WHERE beneficiary_id = b.id
                   ORDER BY assessment_date DESC LIMIT 1
               )$bWhere
             GROUP BY a.nutritional_status"
        );
        $stmt->execute($bParams);
        $statusCounts = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $statusCounts[$row['nutritional_status']] = (int) $row['cnt'];
        }

        $this->success([
            'total_beneficiaries' => $totalBeneficiaries,
            'opt_assessed'        => $optAssessed,
            'dsp_active'          => $dspActive,
            'mns_coverage'        => $mnsCoverage,
            'for_followup'        => $forFollowup,
            'not_yet_assessed'    => $notYetAssessed,
            'suw_count'           => $statusCounts['SUW']    ?? 0,
            'uw_count'            => $statusCounts['UW']     ?? 0,
            'normal_count'        => $statusCounts['Normal'] ?? 0,
            'ow_count'            => $statusCounts['OW']     ?? 0,
            'period'              => $period,
            'year'                => $currentYear,
        ]);
    }
}
