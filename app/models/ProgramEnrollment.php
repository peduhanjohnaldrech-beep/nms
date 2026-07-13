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

        if ($wflhStatus === 'SW')                     $interventionType = 'RUTF';
        elseif ($wflhStatus === 'MW')                 $interventionType = 'RUSF';
        elseif (in_array($wfaStatus, ['SUW','UW']))   $interventionType = 'Health Education';
        else                                          return 0;

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

    public function getActive(string $program): array
    {
        return $this->fetchAll(
            "SELECT pe.*,
                    (SELECT COUNT(*) FROM program_enrollments pe2
                     WHERE pe2.beneficiary_id = pe.beneficiary_id AND pe2.program = pe.program AND pe2.id <= pe.id) AS cycle_number,
                    b.last_name, b.first_name, b.middle_name, b.barangay, b.date_of_birth, b.sex,
                    a.nutritional_status, a.wflh_status, a.assessment_date
             FROM program_enrollments pe
             JOIN beneficiaries b ON b.id = pe.beneficiary_id
             LEFT JOIN assessments a ON a.id = (
                 SELECT id FROM assessments WHERE beneficiary_id = pe.beneficiary_id
                 ORDER BY assessment_date DESC, id DESC LIMIT 1
             )
             WHERE pe.program = ? AND pe.status = 'Active'
             ORDER BY b.last_name, b.first_name",
            [$program]
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

    public function getEligibleForDSP(): array
    {
        return $this->fetchAll(
            "SELECT b.*, a.nutritional_status, a.wflh_status,
                    a.weight_for_age_zscore, a.wflh_zscore, a.weight_kg, a.assessment_date
             FROM beneficiaries b
             JOIN assessments a ON a.id = (
                 SELECT id FROM assessments WHERE beneficiary_id = b.id ORDER BY assessment_date DESC, id DESC LIMIT 1
             )
             WHERE (a.wflh_status IN ('SW','MW') OR a.nutritional_status IN ('SUW','UW'))
             AND b.deleted_at IS NULL
             AND b.id NOT IN (
                 SELECT beneficiary_id FROM program_enrollments WHERE program = 'DSP' AND status = 'Active'
             )
             ORDER BY a.wflh_status, a.nutritional_status, b.last_name"
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
             AND TIMESTAMPDIFF(MONTH, b.date_of_birth, ?) BETWEEN 6 AND 59
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

    public function getHistory(string $program, int $year): array
    {
        return $this->fetchAll(
            "SELECT pe.*,
                    (SELECT COUNT(*) FROM program_enrollments pe2
                     WHERE pe2.beneficiary_id = pe.beneficiary_id AND pe2.program = pe.program AND pe2.id <= pe.id) AS cycle_number,
                    b.last_name, b.first_name, b.barangay, b.date_of_birth
             FROM program_enrollments pe
             JOIN beneficiaries b ON b.id = pe.beneficiary_id
             WHERE pe.program = ? AND pe.status IN ('Completed','Dropped') AND pe.cycle_year = ?
             ORDER BY pe.end_date DESC, b.last_name",
            [$program, $year]
        );
    }

    public function getActiveDSPEnrollment(int $beneficiaryId): ?array
    {
        return $this->fetch(
            "SELECT id FROM program_enrollments WHERE beneficiary_id = ? AND program = 'DSP' AND status = 'Active'",
            [$beneficiaryId]
        ) ?: null;
    }

    public function getReadyToDischarge(): array
    {
        return $this->fetchAll(
            "SELECT pe.*,
                    (SELECT COUNT(*) FROM program_enrollments pe2
                     WHERE pe2.beneficiary_id = pe.beneficiary_id AND pe2.program = 'DSP' AND pe2.id <= pe.id) AS cycle_number,
                    b.last_name, b.first_name, b.barangay, b.date_of_birth,
                    a.nutritional_status, a.wflh_status, a.assessment_date, a.weight_kg
             FROM program_enrollments pe
             JOIN beneficiaries b ON b.id = pe.beneficiary_id
             JOIN assessments a ON a.id = (
                 SELECT id FROM assessments WHERE beneficiary_id = pe.beneficiary_id
                 ORDER BY assessment_date DESC, id DESC LIMIT 1
             )
             WHERE pe.program = 'DSP' AND pe.status = 'Active'
             AND a.nutritional_status NOT IN ('SUW', 'UW')
             AND (a.wflh_status IS NULL OR a.wflh_status NOT IN ('SW', 'MW'))
             ORDER BY b.last_name, b.first_name"
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