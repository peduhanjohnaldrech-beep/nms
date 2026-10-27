<?php

namespace App\Models;

use Core\Model;
use App\Models\Beneficiary;

class MnpRecord extends Model
{
    protected string $table = 'mnp_records';

    public function getByBeneficiary(int $beneficiaryId): array
    {
        return $this->fetchAll(
            "SELECT m.*, u.full_name AS given_by_name
             FROM mnp_records m LEFT JOIN users u ON u.id = m.given_by
             WHERE m.beneficiary_id = ? ORDER BY m.date_given DESC",
            [$beneficiaryId]
        );
    }

    public function getBatches(int $beneficiaryId): array
    {
        return $this->fetchAll(
            "SELECT id, date_given, batch_number FROM mnp_records WHERE beneficiary_id = ? ORDER BY batch_number ASC, date_given ASC",
            [$beneficiaryId]
        );
    }

    public function hasDuplicate(int $beneficiaryId, int $year, string $ageGroup): bool
    {
        return count($this->getBatches($beneficiaryId)) >= 2;
    }

    public function recordDistribution(array $data): int
    {
        $beneficiaryId = (int)$data['beneficiary_id'];
        $dateGiven     = $data['date_given'];

        $batches = $this->getBatches($beneficiaryId);
        if (count($batches) >= 2) {
            throw new \RuntimeException('Maximum 2 MNP batches already given for this beneficiary.');
        }
        if (count($batches) === 1) {
            $days = (int)(new \DateTime($batches[0]['date_given']))->diff(new \DateTime($dateGiven))->days;
            if ($days < 180) {
                throw new \RuntimeException(
                    'MNP Batch 2 requires at least 180 days after Batch 1 (last: ' . $batches[0]['date_given'] . ').'
                );
            }
        }

        $row = [
            'beneficiary_id'      => $beneficiaryId,
            'given_by'            => $data['given_by'] ?? null,
            'date_given'          => $dateGiven,
            'year'                => $data['year'],
            'age_group'           => $data['age_group'],
            'batch_number'        => count($batches) + 1,
            'completed_routine'   => (int)($data['completed_routine'] ?? 0),
            'counseling_given'    => (int)($data['counseling_given'] ?? 0),
            'sachets_distributed' => isset($data['sachets_distributed']) && $data['sachets_distributed'] !== null && $data['sachets_distributed'] !== ''
                ? (int)$data['sachets_distributed'] : 90,
            'notes'               => $data['notes'] ?? null,
        ];
        return $this->insert($row);
    }

    public function getCompletionByBarangay(int $year, string $barangay = ''): array
    {
        $extra = ''; $params = [$year];
        if ($barangay !== '') { $extra .= ' AND b.barangay = ?'; $params[] = $barangay; }
        return $this->fetchAll(
            "SELECT b.barangay, m.age_group, COUNT(*) AS total, SUM(m.completed_routine) AS completed
             FROM mnp_records m JOIN beneficiaries b ON b.id = m.beneficiary_id
             WHERE b.deleted_at IS NULL AND b.validation_status = 'validated' AND m.year = ?$extra
             GROUP BY b.barangay, m.age_group ORDER BY b.barangay, m.age_group",
            $params
        );
    }

    public function getAllByYear(int $year, string $barangay = '', string $purok = ''): array
    {
        $extra = ''; $params = [$year];
        if ($barangay !== '') { $extra .= ' AND b.barangay = ?';                 $params[] = $barangay; }
        if ($purok    !== '') { $extra .= ' AND LOWER(b.purok_zone) = LOWER(?)'; $params[] = $purok; }
        return $this->fetchAll(
            "SELECT m.*, b.last_name, b.first_name, b.barangay, b.date_of_birth,
                    u.full_name AS given_by_name
             FROM mnp_records m
             JOIN beneficiaries b ON b.id = m.beneficiary_id
             LEFT JOIN users u ON u.id = m.given_by
             WHERE b.deleted_at IS NULL AND b.validation_status = 'validated' AND m.year = ?$extra
             ORDER BY m.date_given DESC, b.barangay, b.last_name",
            $params
        );
    }

    public function getNotYetReceived(int $year, string $asOfDate = '', string $barangay = '', string $purok = ''): array
    {
        $ref = $asOfDate ?: date('Y-m-d');
        $extra = ''; $params = [$ref];
        if ($barangay !== '') { $extra .= ' AND b.barangay = ?';                 $params[] = $barangay; }
        if ($purok    !== '') { $extra .= ' AND LOWER(b.purok_zone) = LOWER(?)'; $params[] = $purok; }
        return $this->fetchAll(
            "SELECT b.id, b.last_name, b.first_name, b.barangay, b.date_of_birth,
                    TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) AS age_months,
                    (SELECT COUNT(*) FROM mnp_records mx WHERE mx.beneficiary_id = b.id) AS batches_given,
                    (SELECT MAX(date_given) FROM mnp_records mx WHERE mx.beneficiary_id = b.id) AS last_batch_date
             FROM beneficiaries b
             WHERE b.deleted_at IS NULL AND b.validation_status = 'validated'
               AND " . Beneficiary::BNS_SUBMITTED_FILTER . "
               AND TIMESTAMPDIFF(MONTH, b.date_of_birth, ?) BETWEEN 6 AND 23
               AND (
                   -- Never received MNP
                   (SELECT COUNT(*) FROM mnp_records mx WHERE mx.beneficiary_id = b.id) = 0
                   OR (
                       -- Got Batch 1 only, and 180+ days have passed → due for Batch 2
                       (SELECT COUNT(*) FROM mnp_records mx WHERE mx.beneficiary_id = b.id) = 1
                       AND DATEDIFF(CURDATE(), (SELECT MAX(date_given) FROM mnp_records mx WHERE mx.beneficiary_id = b.id)) >= 180
                   )
               )$extra
             ORDER BY b.barangay, b.last_name",
            $params
        );
    }
}
