<?php

namespace App\Models;

use Core\Model;

class DspMonitoring extends Model
{
    protected string $table = 'dsp_monitoring';

    public function createWithZScore(array $data): int
    {
        $sex    = $data['sex'];
        $age    = (int) $data['age_in_months'];
        $weight = (float) $data['weight_kg'];
        $height = !empty($data['height_cm']) ? (float) $data['height_cm'] : null;

        $wfaZscore = \ZScoreHelper::computeWFA($weight, $age, $sex);
        $wfaStatus = $wfaZscore !== null ? \ZScoreHelper::classifyWFA($wfaZscore) : 'Pending';

        $wflhZscore = null;
        $wflhStatus = null;
        if ($height !== null) {
            $wflh = \ZScoreHelper::computeWFLH($weight, $height, $sex);
            if ($wflh) {
                $wflhZscore = $wflh['zscore'];
                $wflhStatus = $wflh['status'];
            }
        }

        return $this->insert([
            'local_id'              => $data['local_id'] ?? null,
            'enrollment_id'         => $data['enrollment_id'],
            'beneficiary_id'        => $data['beneficiary_id'],
            'monitoring_date'       => $data['monitoring_date'],
            'age_in_months'         => $age,
            'weight_kg'             => $weight,
            'height_cm'             => $height,
            'muac_cm'               => $data['muac_cm'] ?? null,
            'weight_for_age_zscore' => $wfaZscore !== null ? round($wfaZscore, 3) : null,
            'nutritional_status'    => $wfaStatus,
            'wflh_zscore'           => $wflhZscore !== null ? round($wflhZscore, 3) : null,
            'wflh_status'           => $wflhStatus,
            'monitored_by'          => $data['monitored_by'] ?? null,
            'remarks'               => $data['remarks'] ?? null,
            'validation_status'     => $data['validation_status'] ?? 'pending',
            'validated_by'          => $data['validated_by'] ?? null,
            'validated_at'          => $data['validated_at'] ?? null,
            'created_by'            => $data['created_by'] ?? null,
        ]);
    }

    public function getByEnrollment(int $enrollmentId): array
    {
        return $this->fetchAll(
            "SELECT * FROM dsp_monitoring WHERE enrollment_id = ? ORDER BY monitoring_date DESC, id DESC",
            [$enrollmentId]
        );
    }

    public function getLatestByBeneficiary(int $beneficiaryId): ?array
    {
        return $this->fetch(
            "SELECT * FROM dsp_monitoring
             WHERE beneficiary_id = ? AND validation_status = 'validated'
             ORDER BY monitoring_date DESC, id DESC LIMIT 1",
            [$beneficiaryId]
        ) ?: null;
    }

    public function getPendingByBarangay(string $barangay = ''): array
    {
        $where  = "dm.validation_status = 'pending'";
        $params = [];
        if ($barangay !== '') { $where .= ' AND b.barangay = ?'; $params[] = $barangay; }

        return $this->fetchAll(
            "SELECT dm.*, b.last_name, b.first_name, b.barangay, b.sex,
                    pe.intervention_type
             FROM dsp_monitoring dm
             JOIN beneficiaries b   ON b.id  = dm.beneficiary_id
             JOIN program_enrollments pe ON pe.id = dm.enrollment_id
             WHERE $where
             ORDER BY dm.monitoring_date DESC",
            $params
        );
    }

    public function validate(int $id, int $validatedBy): bool
    {
        return $this->update($id, [
            'validation_status' => 'validated',
            'validated_by'      => $validatedBy,
            'validated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    public function reject(int $id, int $rejectedBy, string $reason = ''): bool
    {
        return $this->update($id, [
            'validation_status' => 'rejected',
            'validated_by'      => $rejectedBy,
            'validated_at'      => date('Y-m-d H:i:s'),
            'remarks'           => $reason,
        ]);
    }
}
