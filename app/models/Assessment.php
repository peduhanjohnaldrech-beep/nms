<?php

namespace App\Models;

use Core\Model;

class Assessment extends Model
{
    protected string $table = 'assessments';

    public function findByBeneficiary(int $beneficiaryId): array
    {
        return $this->fetchAll(
            "SELECT * FROM assessments WHERE beneficiary_id = ? ORDER BY assessment_date DESC",
            [$beneficiaryId]
        );
    }

    public function getLatest(int $beneficiaryId): array|false
    {
        return $this->fetch(
            "SELECT * FROM assessments WHERE beneficiary_id = ? ORDER BY assessment_date DESC LIMIT 1",
            [$beneficiaryId]
        );
    }

    public function createWithZScore(array $data): int
    {
        $sex    = $data['sex'];
        $age    = (int) $data['age_in_months'];
        $weight = (float) $data['weight_kg'];
        $height = !empty($data['height_cm']) ? (float) $data['height_cm'] : null;

        $wfaZscore = \ZScoreHelper::computeWFA($weight, $age, $sex);
        $wfaStatus = $wfaZscore !== null ? \ZScoreHelper::classifyWFA($wfaZscore) : 'Normal';

        $hfaZscore = null;
        $hfaStatus = null;
        if ($height !== null) {
            $hfaZscore = \ZScoreHelper::computeHFA($height, $age, $sex);
            $hfaStatus = $hfaZscore !== null ? \ZScoreHelper::classifyHFA($hfaZscore) : null;
        }

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
            'beneficiary_id'        => $data['beneficiary_id'],
            'assessment_date'       => $data['assessment_date'],
            'age_in_months'         => $age,
            'weight_kg'             => $weight,
            'height_cm'             => $height,
            'muac_cm'               => $data['muac_cm'] ?? null,
            'weight_for_age_zscore' => $wfaZscore !== null ? round($wfaZscore, 3) : null,
            'nutritional_status'    => $wfaStatus,
            'height_for_age_zscore' => $hfaZscore !== null ? round($hfaZscore, 3) : null,
            'hfa_status'            => $hfaStatus,
            'wflh_zscore'           => $wflhZscore,
            'wflh_status'           => $wflhStatus,
            'period'                => $data['period'],
            'assessment_year'       => $data['assessment_year'],
            'assessed_by'           => $data['assessed_by'] ?? null,
            'remarks'               => $data['remarks'] ?? null,
            'validation_status'     => $data['validation_status'] ?? 'validated',
            'validated_by'          => $data['validated_by'] ?? null,
            'validated_at'          => $data['validated_at'] ?? null,
            'created_by'            => $data['created_by'] ?? null,
        ]);
    }

    public function getNutritionalStatus(int $year = 0, string $period = ''): array
    {
        $params = [];
        $where  = ['1=1'];
        if ($year > 0)   { $where[] = 'a.assessment_year = ?'; $params[] = $year; }
        if ($period !== '') { $where[] = 'a.period = ?'; $params[] = $period; }

        return $this->fetchAll(
            "SELECT b.barangay, a.nutritional_status AS wfa_status, a.hfa_status, a.wflh_status, COUNT(*) AS cnt
             FROM assessments a JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL AND " . implode(' AND ', $where) . "
             GROUP BY b.barangay, a.nutritional_status, a.hfa_status, a.wflh_status
             ORDER BY b.barangay",
            $params
        );
    }

    public function getStatusTrend(int $beneficiaryId): ?string
    {
        $records = $this->fetchAll(
            "SELECT nutritional_status FROM assessments WHERE beneficiary_id = ? ORDER BY assessment_date DESC LIMIT 2",
            [$beneficiaryId]
        );
        if (count($records) < 2) return null;
        $order   = ['SUW' => 0, 'UW' => 1, 'Normal' => 2, 'OW' => 3, 'OB' => 4];
        $current = $order[$records[0]['nutritional_status']] ?? 2;
        $prev    = $order[$records[1]['nutritional_status']] ?? 2;
        if ($current > $prev) return 'improved';
        if ($current < $prev) return 'worsened';
        return 'same';
    }

    public function getWorsenedBeneficiaries(string $barangay = ''): array
    {
        $where  = 'b.deleted_at IS NULL';
        $params = [];
        if ($barangay !== '') {
            $where   .= ' AND b.barangay = ?';
            $params[] = $barangay;
        }
        return $this->fetchAll(
            "SELECT b.id, b.last_name, b.first_name, b.barangay, b.date_of_birth,
                    a1.nutritional_status AS curr_status,
                    a1.assessment_date    AS last_assessment_date,
                    a2.nutritional_status AS prev_status
             FROM beneficiaries b
             JOIN assessments a1 ON a1.id = (
                 SELECT id FROM assessments WHERE beneficiary_id = b.id ORDER BY assessment_date DESC LIMIT 1
             )
             JOIN assessments a2 ON a2.id = (
                 SELECT id FROM assessments WHERE beneficiary_id = b.id ORDER BY assessment_date DESC LIMIT 1 OFFSET 1
             )
             WHERE $where
               AND CASE a1.nutritional_status WHEN 'SUW' THEN 1 WHEN 'UW' THEN 2 WHEN 'Normal' THEN 3 WHEN 'OW' THEN 4 WHEN 'OB' THEN 5 ELSE 0 END
                 < CASE a2.nutritional_status WHEN 'SUW' THEN 1 WHEN 'UW' THEN 2 WHEN 'Normal' THEN 3 WHEN 'OW' THEN 4 WHEN 'OB' THEN 5 ELSE 0 END
             ORDER BY b.barangay, b.last_name",
            $params
        );
    }

    public function getAgeGroupStats(int $year, string $period = '', string $barangay = ''): array
    {
        $where  = ['a.assessment_year = ?'];
        $params = [$year];
        if ($period !== '')   { $where[] = 'a.period = ?';    $params[] = $period; }
        if ($barangay !== '') { $where[] = 'b.barangay = ?';  $params[] = $barangay; }
        $cond = implode(' AND ', $where);

        return $this->fetchAll(
            "SELECT
                CASE
                    WHEN a.age_in_months BETWEEN 0  AND 5  THEN '0–5 mo'
                    WHEN a.age_in_months BETWEEN 6  AND 11 THEN '6–11 mo'
                    WHEN a.age_in_months BETWEEN 12 AND 23 THEN '12–23 mo'
                    WHEN a.age_in_months BETWEEN 24 AND 35 THEN '24–35 mo'
                    WHEN a.age_in_months BETWEEN 36 AND 47 THEN '36–47 mo'
                    ELSE '48–59 mo'
                END AS age_group,
                a.nutritional_status,
                COUNT(*) AS cnt
             FROM assessments a
             JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL AND $cond
             GROUP BY age_group, a.nutritional_status
             ORDER BY MIN(a.age_in_months), a.nutritional_status",
            $params
        );
    }
}
