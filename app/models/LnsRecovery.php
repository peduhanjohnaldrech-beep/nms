<?php

namespace App\Models;

use Core\Model;

class LnsRecovery extends Model
{
    protected string $table = 'lns_recoveries';

    public function isRecovered(int $beneficiaryId, int $year): bool
    {
        return (bool) $this->fetch(
            "SELECT id FROM lns_recoveries WHERE beneficiary_id = ? AND year = ?",
            [$beneficiaryId, $year]
        );
    }

    public function markRecovered(int $beneficiaryId, int $year, string $recoveryDate, int $recordedBy, string $notes = ''): void
    {
        if ($this->isRecovered($beneficiaryId, $year)) return;
        $this->insert([
            'beneficiary_id' => $beneficiaryId,
            'year'           => $year,
            'recovery_date'  => $recoveryDate,
            'recorded_by'    => $recordedBy,
            'notes'          => $notes ?: null,
        ]);
    }

    public function getByBeneficiary(int $beneficiaryId): array
    {
        return $this->fetchAll(
            "SELECT r.*, u.full_name AS recorded_by_name
             FROM lns_recoveries r LEFT JOIN users u ON u.id = r.recorded_by
             WHERE r.beneficiary_id = ? ORDER BY r.year DESC",
            [$beneficiaryId]
        );
    }
}
