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

    public function getLastDistribution(int $beneficiaryId): ?array
    {
        return $this->fetch(
            "SELECT distribution_date, dose_number FROM vitamin_a_records WHERE beneficiary_id = ? ORDER BY distribution_date DESC, id DESC LIMIT 1",
            [$beneficiaryId]
        ) ?: null;
    }

    public function getDoseCount(int $beneficiaryId): int
    {
        $row = $this->fetch("SELECT COUNT(*) AS cnt FROM vitamin_a_records WHERE beneficiary_id = ?", [$beneficiaryId]);
        return (int)($row['cnt'] ?? 0);
    }

    public function recordDistribution(array $data): int
    {
        if (empty($data['dosage_iu'])) {
            $dosageInfo = $this->getDosageForAge((int) $data['age_months']);
            if (!$dosageInfo) throw new \InvalidArgumentException('Beneficiary not eligible for Vitamin A.');
            $data['dosage_iu']     = $dosageInfo['dosage_iu'];
            $data['capsule_color'] = $dosageInfo['capsule_color'];
        }

        $beneficiaryId = (int)$data['beneficiary_id'];
        $distribDate   = $data['distribution_date'] ?? date('Y-m-d');

        // 6-month (180-day) interval check
        $last = $this->getLastDistribution($beneficiaryId);
        if ($last) {
            $days = (int)(new \DateTime($last['distribution_date']))->diff(new \DateTime($distribDate))->days;
            if ($days < 180) {
                throw new \RuntimeException(
                    'Vitamin A was given within the last 6 months (last: ' . $last['distribution_date'] . '). Must wait 180 days.'
                );
            }
        }

        // Max 9 lifetime doses (6–59 months, biannual rounds)
        $doseCount = $this->getDoseCount($beneficiaryId);
        if ($doseCount >= 9) {
            throw new \RuntimeException('Maximum 9 Vitamin A doses already recorded for this beneficiary.');
        }

        return $this->insert([
            'beneficiary_id'    => $beneficiaryId,
            'distribution_date' => $distribDate,
            'round'             => $data['round'],
            'year'              => $data['year'] ?? date('Y'),
            'dosage_iu'         => $data['dosage_iu'],
            'capsule_color'     => $data['capsule_color'],
            'dose_number'       => $doseCount + 1,
            'administered_by'   => $data['administered_by'] ?? null,
            'created_by'        => $data['created_by'] ?? null,
        ]);
    }

    public function getAllByRound(string $round, int $year, string $barangay = '', string $purok = ''): array
    {
        $extra = ''; $params = [$round, $year];
        if ($barangay !== '') { $extra .= ' AND b.barangay = ?';                 $params[] = $barangay; }
        if ($purok    !== '') { $extra .= ' AND LOWER(b.purok_zone) = LOWER(?)'; $params[] = $purok; }
        return $this->fetchAll(
            "SELECT v.*, b.last_name, b.first_name, b.barangay, b.date_of_birth
             FROM vitamin_a_records v
             JOIN beneficiaries b ON b.id = v.beneficiary_id
             WHERE b.deleted_at IS NULL AND b.validation_status = 'validated'
               AND v.round = ? AND v.year = ?$extra
             ORDER BY b.barangay, b.last_name",
            $params
        );
    }

    public function getCoverageByBarangay(string $round, int $year, string $barangay = ''): array
    {
        $extra = ''; $params = [$round, $year];
        if ($barangay !== '') { $extra .= ' AND b.barangay = ?'; $params[] = $barangay; }
        return $this->fetchAll(
            "SELECT b.barangay,
                    COUNT(DISTINCT v.beneficiary_id) AS covered,
                    SUM(CASE WHEN v.capsule_color = 'Blue' THEN 1 ELSE 0 END) AS blue_count,
                    SUM(CASE WHEN v.capsule_color = 'Red' THEN 1 ELSE 0 END) AS red_count
             FROM vitamin_a_records v
             JOIN beneficiaries b ON b.id = v.beneficiary_id
             WHERE b.deleted_at IS NULL AND b.validation_status = 'validated'
               AND v.round = ? AND v.year = ?$extra
             GROUP BY b.barangay ORDER BY b.barangay",
            $params
        );
    }
}
