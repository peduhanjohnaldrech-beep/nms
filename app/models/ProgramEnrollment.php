<?php

namespace App\Models;

use Core\Model;

class ProgramEnrollment extends Model
{
    protected string $table = 'program_enrollments';

    public function enrollBeneficiary(int $beneficiaryId, string $program, array $extra = []): int
    {
        $existing = $this->fetch(
            "SELECT id FROM program_enrollments WHERE beneficiary_id = ? AND program = ? AND status = 'Active'",
            [$beneficiaryId, $program]
        );
        if ($existing) return 0;

        $row = [
            'beneficiary_id'  => $beneficiaryId,
            'program'         => $program,
            'enrollment_date' => $extra['enrollment_date'] ?? date('Y-m-d'),
            'status'          => 'Active',
            'cycle_year'      => $extra['cycle_year'] ?? date('Y'),
            'notes'           => $extra['notes'] ?? null,
            'enrolled_by'     => $extra['enrolled_by'] ?? null,
        ];
        if (isset($extra['intervention_type'])) $row['intervention_type'] = $extra['intervention_type'];
        if (isset($extra['pre_weight_kg']))      $row['pre_weight_kg']     = $extra['pre_weight_kg'];

        return $this->insert($row);
    }

    public function autoEnrollDSP(int $assessmentId): int
    {
        $assessment = (new Assessment())->findById($assessmentId);
        if (!$assessment) return 0;

        $wflhStatus = $assessment['wflh_status'] ?? null;
        $wfaStatus  = $assessment['nutritional_status'] ?? null;

        if ($wflhStatus === 'SW' || $wfaStatus === 'SUW') $interventionType = 'RUTF';
        elseif ($wflhStatus === 'MW' || $wfaStatus === 'UW') $interventionType = 'Supplementary Feeding';
        else                                               return 0;

        return $this->enrollBeneficiary($assessment['beneficiary_id'], 'DSP', [
            'enrollment_date'   => $assessment['assessment_date'],
            'cycle_year'        => $assessment['assessment_year'],
            'intervention_type' => $interventionType,
            'pre_weight_kg'     => $assessment['weight_kg'],
            'notes'             => sprintf(
                'Auto-enrolled from assessment #%d. WFL/H: %s, WFA: %s',
                $assessmentId, $wflhStatus ?? 'N/A', $wfaStatus ?? 'N/A'
            ),
        ]);
    }

    public function getActive(string $program, string $barangay = '', string $purok = ''): array
    {
        $where  = "pe.program = ? AND pe.status = 'Active' AND TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) <= 59";
        $params = [$program];
        if ($barangay !== '') { $where .= ' AND b.barangay = ?';                 $params[] = $barangay; }
        if ($purok    !== '') { $where .= ' AND LOWER(b.purok_zone) = LOWER(?)'; $params[] = $purok; }

        // DSP uses dsp_monitoring for latest treatment status; other programs use assessments
        if ($program === 'DSP') {
            return $this->fetchAll(
                "SELECT pe.*,
                        (SELECT COUNT(*) FROM program_enrollments pe2
                         WHERE pe2.beneficiary_id = pe.beneficiary_id AND pe2.program = pe.program AND pe2.id <= pe.id) AS cycle_number,
                        b.last_name, b.first_name, b.middle_name, b.barangay, b.purok_zone, b.date_of_birth, b.sex,
                        dm.nutritional_status, dm.wflh_status, dm.monitoring_date AS assessment_date, dm.muac_cm,
                        (SELECT nutritional_status FROM dsp_monitoring
                         WHERE enrollment_id = pe.id AND validation_status = 'validated'
                         ORDER BY monitoring_date DESC, id DESC LIMIT 1 OFFSET 1) AS prev_nutritional_status,
                        (SELECT wflh_status FROM dsp_monitoring
                         WHERE enrollment_id = pe.id AND validation_status = 'validated'
                         ORDER BY monitoring_date DESC, id DESC LIMIT 1 OFFSET 1) AS prev_wflh_status,
                        TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) AS age_months
                 FROM program_enrollments pe
                 JOIN beneficiaries b ON b.id = pe.beneficiary_id
                 LEFT JOIN dsp_monitoring dm ON dm.id = (
                     SELECT id FROM dsp_monitoring WHERE enrollment_id = pe.id
                     AND validation_status = 'validated'
                     ORDER BY monitoring_date DESC, id DESC LIMIT 1
                 )
                 WHERE $where
                 ORDER BY b.last_name, b.first_name",
                $params
            );
        }

        return $this->fetchAll(
            "SELECT pe.*,
                    (SELECT COUNT(*) FROM program_enrollments pe2
                     WHERE pe2.beneficiary_id = pe.beneficiary_id AND pe2.program = pe.program AND pe2.id <= pe.id) AS cycle_number,
                    b.last_name, b.first_name, b.middle_name, b.barangay, b.purok_zone, b.date_of_birth, b.sex,
                    a.nutritional_status, a.wflh_status, a.assessment_date, a.muac_cm,
                    (SELECT nutritional_status FROM assessments
                     WHERE beneficiary_id = pe.beneficiary_id AND validation_status = 'validated'
                     ORDER BY assessment_date DESC, id DESC LIMIT 1 OFFSET 1) AS prev_nutritional_status,
                    (SELECT wflh_status FROM assessments
                     WHERE beneficiary_id = pe.beneficiary_id AND validation_status = 'validated'
                     ORDER BY assessment_date DESC, id DESC LIMIT 1 OFFSET 1) AS prev_wflh_status,
                    TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) AS age_months
             FROM program_enrollments pe
             JOIN beneficiaries b ON b.id = pe.beneficiary_id
             LEFT JOIN assessments a ON a.id = (
                 SELECT id FROM assessments WHERE beneficiary_id = pe.beneficiary_id
                 AND validation_status = 'validated'
                 ORDER BY assessment_date DESC, id DESC LIMIT 1
             )
             WHERE $where
             ORDER BY b.last_name, b.first_name",
            $params
        );
    }

    public function complete(int $enrollmentId, ?float $postWeight = null): bool
    {
        $data = ['status' => 'Completed', 'end_date' => date('Y-m-d')];
        if ($postWeight !== null) $data['post_weight_kg'] = $postWeight;
        return $this->update($enrollmentId, $data);
    }

    public function drop(int $enrollmentId): bool
    {
        return $this->update($enrollmentId, ['status' => 'Dropped', 'end_date' => date('Y-m-d')]);
    }

    public function getEligibleForDSP(string $barangay = '', string $purok = ''): array
    {
        $extra  = '';
        $params = [];
        if ($barangay !== '') { $extra .= ' AND b.barangay = ?';                 $params[] = $barangay; }
        if ($purok    !== '') { $extra .= ' AND LOWER(b.purok_zone) = LOWER(?)'; $params[] = $purok; }

        return $this->fetchAll(
            "SELECT b.*, a.nutritional_status, a.wflh_status,
                    a.weight_for_age_zscore, a.wflh_zscore, a.weight_kg, a.assessment_date
             FROM beneficiaries b
             JOIN assessments a ON a.id = (
                 SELECT id FROM assessments WHERE beneficiary_id = b.id ORDER BY assessment_date DESC, id DESC LIMIT 1
             )
             WHERE (a.wflh_status IN ('SW','MW') OR a.nutritional_status IN ('SUW','UW'))
             AND b.deleted_at IS NULL
             AND b.validation_status = 'validated'
             AND TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) <= 59
             AND NOT (b.source = 'Mobile' AND b.submitted_at IS NULL AND EXISTS (SELECT 1 FROM users u WHERE u.id = b.created_by AND LOWER(u.role) = 'bns'))
             AND b.id NOT IN (
                 SELECT beneficiary_id FROM program_enrollments WHERE program = 'DSP' AND status = 'Active'
             )$extra
             ORDER BY a.wflh_status, a.nutritional_status, b.last_name",
            $params
        );
    }

    public function getEligibleForMNS(string $round, int $year): array
    {
        $refDate = $round === 'February' ? "{$year}-02-15" : "{$year}-08-15";
        return $this->fetchAll(
            "SELECT b.*,
                    TIMESTAMPDIFF(MONTH, b.date_of_birth, ?) AS age_months
             FROM beneficiaries b
             WHERE b.deleted_at IS NULL
             AND b.validation_status = 'validated'
             AND NOT (b.source = 'Mobile' AND b.submitted_at IS NULL AND EXISTS (SELECT 1 FROM users u WHERE u.id = b.created_by AND LOWER(u.role) = 'bns'))
             AND TIMESTAMPDIFF(MONTH, b.date_of_birth, ?) BETWEEN 6 AND 59
             AND TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) <= 59
             AND b.id NOT IN (
                 SELECT beneficiary_id FROM vitamin_a_records WHERE round = ? AND year = ?
             )
             ORDER BY b.barangay, b.last_name",
            [$refDate, $refDate, $round, $year]
        );
    }

    public function findByBeneficiary(int $beneficiaryId): array
    {
        return $this->fetchAll(
            "SELECT * FROM program_enrollments WHERE beneficiary_id = ? ORDER BY enrollment_date DESC",
            [$beneficiaryId]
        );
    }

    public function getNotEnrolledInProgram(string $program): array
    {
        return $this->fetchAll(
            "SELECT b.id, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.sex
             FROM beneficiaries b
             WHERE b.deleted_at IS NULL
             AND b.validation_status = 'validated'
             AND TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) <= 59
             AND b.id NOT IN (
                 SELECT beneficiary_id FROM program_enrollments
                 WHERE program = ? AND status = 'Active'
             )
             ORDER BY b.barangay, b.last_name",
            [$program]
        );
    }

    public function getFiltered(string $program, int $year, string $barangay = ''): array
    {
        $where  = "pe.program = ? AND pe.status = 'Active' AND pe.cycle_year = ?";
        $params = [$program, $year];
        if ($barangay !== '') { $where .= ' AND b.barangay = ?'; $params[] = $barangay; }
        return $this->fetchAll(
            "SELECT pe.*, b.last_name, b.first_name, b.middle_name, b.barangay, b.date_of_birth, b.sex
             FROM program_enrollments pe
             JOIN beneficiaries b ON b.id = pe.beneficiary_id
             WHERE $where ORDER BY b.barangay, b.last_name, b.first_name",
            $params
        );
    }

    public function getHistory(string $program, int $year, string $barangay = '', string $purok = ''): array
    {
        $extra  = '';
        $params = [$program, $year];
        if ($barangay !== '') { $extra .= ' AND b.barangay = ?';                 $params[] = $barangay; }
        if ($purok    !== '') { $extra .= ' AND LOWER(b.purok_zone) = LOWER(?)'; $params[] = $purok; }

        return $this->fetchAll(
            "SELECT pe.*,
                    (SELECT COUNT(*) FROM program_enrollments pe2
                     WHERE pe2.beneficiary_id = pe.beneficiary_id AND pe2.program = pe.program AND pe2.id <= pe.id) AS cycle_number,
                    b.last_name, b.first_name, b.barangay, b.purok_zone, b.date_of_birth
             FROM program_enrollments pe
             JOIN beneficiaries b ON b.id = pe.beneficiary_id
             WHERE pe.program = ? AND pe.status IN ('Completed','Dropped') AND pe.cycle_year = ?$extra
             ORDER BY pe.end_date DESC, b.last_name",
            $params
        );
    }

    public function getActiveDSPEnrollment(int $beneficiaryId): ?array
    {
        return $this->fetch(
            "SELECT id FROM program_enrollments WHERE beneficiary_id = ? AND program = 'DSP' AND status = 'Active'",
            [$beneficiaryId]
        ) ?: null;
    }

    public function getReadyToDischarge(string $barangay = '', string $purok = ''): array
    {
        $extra  = '';
        $params = [];
        if ($barangay !== '') { $extra .= ' AND b.barangay = ?';                 $params[] = $barangay; }
        if ($purok    !== '') { $extra .= ' AND LOWER(b.purok_zone) = LOWER(?)'; $params[] = $purok; }

        return $this->fetchAll(
            "SELECT pe.*,
                    (SELECT COUNT(*) FROM program_enrollments pe2
                     WHERE pe2.beneficiary_id = pe.beneficiary_id AND pe2.program = 'DSP' AND pe2.id <= pe.id) AS cycle_number,
                    b.last_name, b.first_name, b.barangay, b.purok_zone, b.date_of_birth,
                    dm.nutritional_status, dm.wflh_status, dm.monitoring_date AS assessment_date,
                    dm.weight_kg, dm.muac_cm
             FROM program_enrollments pe
             JOIN beneficiaries b ON b.id = pe.beneficiary_id
             LEFT JOIN dsp_monitoring dm ON dm.id = (
                 SELECT id FROM dsp_monitoring WHERE enrollment_id = pe.id
                 AND validation_status = 'validated'
                 ORDER BY monitoring_date DESC, id DESC LIMIT 1
             )
             WHERE pe.program = 'DSP' AND pe.status = 'Active'$extra
             AND (
                 TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) > 59
                 OR (
                     dm.nutritional_status NOT IN ('SUW', 'UW')
                     AND (dm.wflh_status IS NULL OR dm.wflh_status NOT IN ('SW', 'MW'))
                     AND dm.muac_cm IS NOT NULL
                     AND 2 = (
                         SELECT COUNT(*)
                         FROM dsp_monitoring dm_c
                         WHERE dm_c.enrollment_id = pe.id
                           AND dm_c.validation_status = 'validated'
                           AND dm_c.nutritional_status NOT IN ('SUW', 'UW')
                           AND (dm_c.wflh_status IS NULL OR dm_c.wflh_status NOT IN ('SW', 'MW'))
                           AND (
                               SELECT COUNT(*)
                               FROM dsp_monitoring dm_r
                               WHERE dm_r.enrollment_id = pe.id
                                 AND dm_r.validation_status = 'validated'
                                 AND (dm_r.monitoring_date > dm_c.monitoring_date
                                      OR (dm_r.monitoring_date = dm_c.monitoring_date AND dm_r.id > dm_c.id))
                           ) < 2
                     )
                 )
             )
             ORDER BY b.last_name, b.first_name",
            $params
        );
    }

    public function getStats(string $program): array
    {
        $rows = $this->fetchAll(
            "SELECT status, COUNT(*) AS cnt FROM program_enrollments
             WHERE program = ? GROUP BY status",
            [$program]
        );
        $stats = ['Active' => 0, 'Completed' => 0, 'Dropped' => 0];
        foreach ($rows as $r) {
            if (isset($stats[$r['status']])) $stats[$r['status']] = (int)$r['cnt'];
        }
        return $stats;
    }

}