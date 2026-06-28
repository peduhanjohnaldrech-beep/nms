<?php

namespace App\Models;

use Core\Model;

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

    public function hasDuplicate(int $beneficiaryId, int $year, string $ageGroup): bool
    {
        return (bool) $this->fetch(
            "SELECT id FROM mnp_records WHERE beneficiary_id = ? AND year = ? AND age_group = ?",
            [$beneficiaryId, $year, $ageGroup]
        );
    }

    public function recordDistribution(array $data): int
    {
        if ($this->hasDuplicate((int)$data['beneficiary_id'], (int)$data['year'], $data['age_group'])) {
            throw new \RuntimeException('MNP record already exists for ' . $data['age_group'] . ' / ' . $data['year']);
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
            "SELECT b.barangay, m.age_group, COUNT(*) AS total, SUM(m.completed_routine) AS completed
             FROM mnp_records m JOIN beneficiaries b ON b.id = m.beneficiary_id
             WHERE m.year = ?
             GROUP BY b.barangay, m.age_group ORDER BY b.barangay, m.age_group",
            [$year]
        );
    }

    public function getAllByYear(int $year): array
    {
        return $this->fetchAll(
            "SELECT m.*, b.last_name, b.first_name, b.barangay, b.date_of_birth,
                    u.full_name AS given_by_name
             FROM mnp_records m
             JOIN beneficiaries b ON b.id = m.beneficiary_id
             LEFT JOIN users u ON u.id = m.given_by
             WHERE m.year = ?
             ORDER BY m.date_given DESC, b.barangay, b.last_name",
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
                   + CASE WHEN strftime('%d', ?) < strftime('%d', b.date_of_birth) THEN -1 ELSE 0 END BETWEEN 6 AND 59
               AND NOT EXISTS (
                   SELECT 1 FROM mnp_records m WHERE m.beneficiary_id = b.id AND m.year = ?
               )
             ORDER BY b.barangay, b.last_name",
            [$ref, $ref, $ref, $ref, $ref, $ref, $year]
        );
    }
}
