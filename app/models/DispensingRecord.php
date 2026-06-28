<?php

namespace App\Models;

use Core\Model;
use Core\Database;

class DispensingRecord extends Model
{
    protected string $table = 'dispensing_records';

    public function __construct()
    {
        parent::__construct();
        $this->db->exec("CREATE TABLE IF NOT EXISTS dispensing_records (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            beneficiary_id  INTEGER NOT NULL REFERENCES beneficiaries(id),
            enrollment_id   INTEGER REFERENCES program_enrollments(id),
            program         TEXT    NOT NULL,
            supplement_type TEXT    NOT NULL,
            quantity        REAL    NOT NULL DEFAULT 1,
            unit            TEXT    NOT NULL DEFAULT 'piece(s)',
            date_dispensed  TEXT    NOT NULL,
            dispensed_by    INTEGER REFERENCES users(id),
            notes           TEXT,
            created_at      TEXT    DEFAULT (datetime('now'))
        )");
    }

    public function getByBeneficiary(int $beneficiaryId): array
    {
        return $this->fetchAll(
            "SELECT dr.*, u.full_name AS dispensed_by_name
             FROM dispensing_records dr
             LEFT JOIN users u ON u.id = dr.dispensed_by
             WHERE dr.beneficiary_id = ?
             ORDER BY dr.date_dispensed DESC",
            [$beneficiaryId]
        );
    }

    public function getAll(int $year = 0, string $program = '', string $barangay = '', int $beneficiaryId = 0): array
    {
        $where  = ['b.deleted_at IS NULL'];
        $params = [];

        if ($year) {
            $where[]  = "CAST(strftime('%Y', dr.date_dispensed) AS INTEGER) = ?";
            $params[] = $year;
        }
        if ($program) {
            $where[]  = 'dr.program = ?';
            $params[] = $program;
        }
        if ($barangay) {
            $where[]  = 'b.barangay = ?';
            $params[] = $barangay;
        }
        if ($beneficiaryId) {
            $where[]  = 'dr.beneficiary_id = ?';
            $params[] = $beneficiaryId;
        }

        return $this->fetchAll(
            "SELECT dr.*, b.last_name, b.first_name, b.barangay, b.date_of_birth,
                    u.full_name AS dispensed_by_name
             FROM dispensing_records dr
             JOIN  beneficiaries b ON b.id = dr.beneficiary_id
             LEFT JOIN users u ON u.id = dr.dispensed_by
             WHERE " . implode(' AND ', $where) . "
             ORDER BY dr.date_dispensed DESC, b.barangay, b.last_name",
            $params
        );
    }

    public function getSummary(int $year): array
    {
        return $this->fetchAll(
            "SELECT supplement_type, COUNT(*) AS cnt, SUM(quantity) AS total_qty
             FROM dispensing_records
             WHERE CAST(strftime('%Y', date_dispensed) AS INTEGER) = ?
             GROUP BY supplement_type
             ORDER BY cnt DESC",
            [$year]
        );
    }

    public function recordDispensing(array $data): int
    {
        return $this->insert([
            'beneficiary_id'  => $data['beneficiary_id'],
            'enrollment_id'   => $data['enrollment_id'] ?? null,
            'program'         => $data['program'],
            'supplement_type' => $data['supplement_type'],
            'quantity'        => $data['quantity'] ?? 1,
            'unit'            => $data['unit'] ?? 'piece(s)',
            'date_dispensed'  => $data['date_dispensed'] ?? date('Y-m-d'),
            'dispensed_by'    => $data['dispensed_by'] ?? null,
            'notes'           => $data['notes'] ?? null,
        ]);
    }
}
