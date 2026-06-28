<?php

namespace App\Models;

use Core\Model;

class VitaminARecord extends Model
{
    protected string $table = 'vitamin_a_records';

    public function getByBeneficiary(int $beneficiaryId): array
    {
        return $this->fetchAll(
            "SELECT * FROM vitamin_a_records WHERE beneficiary_id = ? ORDER BY distribution_date DESC",
            [$beneficiaryId]
        );
    }

    public function getDosageForAge(int $ageMonths): ?array
    {
        if ($ageMonths >= 6 && $ageMonths <= 11)  return ['dosage_iu' => 100000, 'capsule_color' => 'Blue'];
        if ($ageMonths >= 12 && $ageMonths <= 59) return ['dosage_iu' => 200000, 'capsule_color' => 'Red'];
        return null;
    }

    public function recordDistribution(array $data): int
    {
        if (empty($data['dosage_iu'])) {
            $dosageInfo = $this->getDosageForAge((int) $data['age_months']);
            if (!$dosageInfo) throw new \InvalidArgumentException('Beneficiary not eligible for Vitamin A.');
            $data['dosage_iu']     = $dosageInfo['dosage_iu'];
            $data['capsule_color'] = $dosageInfo['capsule_color'];
        }
        return $this->insert([
            'beneficiary_id'    => $data['beneficiary_id'],
            'distribution_date' => $data['distribution_date'] ?? date('Y-m-d'),
            'round'             => $data['round'],
            'year'              => $data['year'] ?? date('Y'),
            'dosage_iu'         => $data['dosage_iu'],
            'capsule_color'     => $data['capsule_color'],
            'administered_by'   => $data['administered_by'] ?? null,
            'created_by'        => $data['created_by'] ?? null,
        ]);
    }

    public function getAllByRound(string $round, int $year): array
    {
        return $this->fetchAll(
            "SELECT v.*, b.last_name, b.first_name, b.barangay, b.date_of_birth
             FROM vitamin_a_records v
             JOIN beneficiaries b ON b.id = v.beneficiary_id
             WHERE v.round = ? AND v.year = ?
             ORDER BY b.barangay, b.last_name",
            [$round, $year]
        );
    }

    public function getCoverageByBarangay(string $round, int $year): array
    {
        return $this->fetchAll(
            "SELECT b.barangay,
                    COUNT(DISTINCT v.beneficiary_id) AS covered,
                    SUM(CASE WHEN v.capsule_color = 'Blue' THEN 1 ELSE 0 END) AS blue_count,
                    SUM(CASE WHEN v.capsule_color = 'Red' THEN 1 ELSE 0 END) AS red_count
             FROM vitamin_a_records v
             JOIN beneficiaries b ON b.id = v.beneficiary_id
             WHERE v.round = ? AND v.year = ?
             GROUP BY b.barangay ORDER BY b.barangay",
            [$round, $year]
        );
    }
}
