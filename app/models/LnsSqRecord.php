<?php

namespace App\Models;

use Core\Model;

class LnsSqRecord extends Model
{
    protected string $table = 'lns_sq_records';

    public function getByBeneficiary(int $beneficiaryId): array
    {
        return $this->fetchAll(
            "SELECT l.*, u.full_name AS given_by_name
             FROM lns_sq_records l LEFT JOIN users u ON u.id = l.given_by
             WHERE l.beneficiary_id = ? ORDER BY l.date_given DESC",
            [$beneficiaryId]
        );
    }

    public function hasDuplicate(int $beneficiaryId, int $year, string $ageGroup): bool
    {
        return (bool) $this->fetch(
            "SELECT id FROM lns_sq_records WHERE beneficiary_id = ? AND year = ? AND age_group = ?",
            [$beneficiaryId, $year, $ageGroup]
        );
    }

    public function recordDistribution(array $data): int
    {
        if ($this->hasDuplicate((int)$data['beneficiary_id'], (int)$data['year'], $data['age_group'])) {
            throw new \RuntimeException('LNS-SQ record already exists for ' . $data['age_group'] . ' / ' . $data['year']);
        }
        return $this->insert([
            'beneficiary_id'    => $data['beneficiary_id'],
            'given_by'          => $data['given_by'] ?? null,
            'date_given'        => $data['date_given'],
            'year'              => $data['year'],
            'age_group'         => $data['age_group'],
            'completed_routine' => (int)($data['completed_routine'] ?? 0),
            'notes'             => $data['notes'] ?? null,
        ]);
    }

    public function getCompletionByBarangay(int $year): array
    {
        return $this->fetchAll(
            "SELECT b.barangay, l.age_group, COUNT(*) AS total, SUM(l.completed_routine) AS completed
             FROM lns_sq_records l JOIN beneficiaries b ON b.id = l.beneficiary_id
             WHERE l.year = ?
             GROUP BY b.barangay, l.age_group ORDER BY b.barangay, l.age_group",
            [$year]
        );
    }

    public function getAllByYear(int $year): array
    {
        return $this->fetchAll(
            "SELECT l.*, b.last_name, b.first_name, b.barangay, b.date_of_birth,
                    u.full_name AS given_by_name
             FROM lns_sq_records l
             JOIN beneficiaries b ON b.id = l.beneficiary_id
             LEFT JOIN users u ON u.id = l.given_by
             WHERE l.year = ?
             ORDER BY l.date_given DESC, b.barangay, b.last_name",
            [$year]
        );
    }

    public function getNotYetReceived(int $year, string $asOfDate = ''): array
    {
        $ref = $asOfDate ?: date('Y-m-d');
        return $this->fetchAll(
            "SELECT b.id, b.last_name, b.first_name, b.barangay, b.date_of_birth,
                    (CAST(strftime('%Y', ?) AS INTEGER) - CAST(strftime('%Y', b.date_of_birth) AS INTEGER)) * 12
                    + (CAST(strftime('%m', ?) AS INTEGER) - CAST(strftime('%m', b.date_of_birth) AS INTEGER))
                    + CASE WHEN strftime('%d', ?) < strftime('%d', b.date_of_birth) THEN -1 ELSE 0 END AS age_months
             FROM beneficiaries b
             WHERE b.deleted_at IS NULL
               AND (CAST(strftime('%Y', ?) AS INTEGER) - CAST(strftime('%Y', b.date_of_birth) AS INTEGER)) * 12
                   + (CAST(strftime('%m', ?) AS INTEGER) - CAST(strftime('%m', b.date_of_birth) AS INTEGER))
                   + CASE WHEN strftime('%d', ?) < strftime('%d', b.date_of_birth) THEN -1 ELSE 0 END BETWEEN 6 AND 23
               AND NOT EXISTS (
                   SELECT 1 FROM lns_sq_records l WHERE l.beneficiary_id = b.id AND l.year = ?
               )
             ORDER BY b.barangay, b.last_name",
            [$ref, $ref, $ref, $ref, $ref, $ref, $year]
        );
    }
}
