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
            id              INT PRIMARY KEY AUTO_INCREMENT,
            beneficiary_id  INT NOT NULL,
            enrollment_id   INT,
            program         VARCHAR(50)  NOT NULL,
            supplement_type VARCHAR(100) NOT NULL,
            quantity        DECIMAL(10,2) NOT NULL DEFAULT 1,
            unit            VARCHAR(50)  NOT NULL DEFAULT 'piece(s)',
            date_dispensed  DATE         NOT NULL,
            dispensed_by    INT,
            notes           TEXT,
            created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
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
        // Only include MNP/LNS rows when program filter is blank or matches MNS
        $includeMns = !$program || $program === 'MNS';

        // --- dispensing_records params ---
        $drParams = [];
        $drWhere  = "b.deleted_at IS NULL";
        if ($year)          { $drWhere .= " AND YEAR(dr.date_dispensed) = ?"; $drParams[] = $year; }
        if ($program)       { $drWhere .= " AND dr.program = ?";              $drParams[] = $program; }
        if ($barangay)      { $drWhere .= " AND b.barangay = ?";              $drParams[] = $barangay; }
        if ($beneficiaryId) { $drWhere .= " AND dr.beneficiary_id = ?";       $drParams[] = $beneficiaryId; }

        $sql = "SELECT dr.id, dr.beneficiary_id, dr.date_dispensed, dr.program,
                       dr.supplement_type, dr.quantity, dr.unit, dr.notes,
                       b.last_name, b.first_name, b.barangay, b.date_of_birth,
                       u.full_name AS dispensed_by_name
                FROM dispensing_records dr
                JOIN beneficiaries b ON b.id = dr.beneficiary_id
                LEFT JOIN users u ON u.id = dr.dispensed_by
                WHERE $drWhere";
        $allParams = $drParams;

        if ($includeMns) {
            // --- vitamin_a_records params ---
            $vitaParams = [];
            $vitaWhere  = "b.deleted_at IS NULL";
            if ($year)          { $vitaWhere .= " AND v.year = ?";              $vitaParams[] = $year; }
            if ($barangay)      { $vitaWhere .= " AND b.barangay = ?";          $vitaParams[] = $barangay; }
            if ($beneficiaryId) { $vitaWhere .= " AND v.beneficiary_id = ?";    $vitaParams[] = $beneficiaryId; }

            $sql .= "
                UNION ALL
                SELECT v.id, v.beneficiary_id, v.distribution_date AS date_dispensed, 'MNS' AS program,
                       CASE WHEN v.dosage_iu = 100000 THEN '100,000 IU Vitamin A (Blue)' ELSE '200,000 IU Vitamin A (Red)' END AS supplement_type,
                       1 AS quantity, 'capsule' AS unit,
                       CONCAT(v.round, ' ', v.year, ' round') AS notes,
                       b.last_name, b.first_name, b.barangay, b.date_of_birth,
                       v.administered_by AS dispensed_by_name
                FROM vitamin_a_records v
                JOIN beneficiaries b ON b.id = v.beneficiary_id
                WHERE $vitaWhere";
            $allParams = array_merge($allParams, $vitaParams);

            // --- mnp_records params ---
            $mnpParams = [];
            $mnpWhere  = "b.deleted_at IS NULL";
            if ($year)          { $mnpWhere .= " AND YEAR(m.date_given) = ?";   $mnpParams[] = $year; }
            if ($barangay)      { $mnpWhere .= " AND b.barangay = ?";           $mnpParams[] = $barangay; }
            if ($beneficiaryId) { $mnpWhere .= " AND m.beneficiary_id = ?";     $mnpParams[] = $beneficiaryId; }

            $sql .= "
                UNION ALL
                SELECT m.id, m.beneficiary_id, m.date_given AS date_dispensed, 'MNS' AS program,
                       'MNP (Micronutrient Powder)' AS supplement_type, 1 AS quantity, 'sachet' AS unit,
                       m.age_group AS notes,
                       b.last_name, b.first_name, b.barangay, b.date_of_birth,
                       u.full_name AS dispensed_by_name
                FROM mnp_records m
                JOIN beneficiaries b ON b.id = m.beneficiary_id
                LEFT JOIN users u ON u.id = m.given_by
                WHERE $mnpWhere";
            $allParams = array_merge($allParams, $mnpParams);

            // --- lns_sq_records params ---
            $lnsParams = [];
            $lnsWhere  = "b.deleted_at IS NULL";
            if ($year)          { $lnsWhere .= " AND YEAR(l.date_given) = ?";   $lnsParams[] = $year; }
            if ($barangay)      { $lnsWhere .= " AND b.barangay = ?";           $lnsParams[] = $barangay; }
            if ($beneficiaryId) { $lnsWhere .= " AND l.beneficiary_id = ?";     $lnsParams[] = $beneficiaryId; }

            $sql .= "
                UNION ALL
                SELECT l.id, l.beneficiary_id, l.date_given AS date_dispensed, 'MNS' AS program,
                       'LNS-SQ (Lipid-based Nutrient Supplement)' AS supplement_type, 1 AS quantity, 'sachet' AS unit,
                       l.age_group AS notes,
                       b.last_name, b.first_name, b.barangay, b.date_of_birth,
                       u.full_name AS dispensed_by_name
                FROM lns_sq_records l
                JOIN beneficiaries b ON b.id = l.beneficiary_id
                LEFT JOIN users u ON u.id = l.given_by
                WHERE $lnsWhere";
            $allParams = array_merge($allParams, $lnsParams);
        }

        $sql .= " ORDER BY date_dispensed DESC, barangay, last_name LIMIT 2000";

        return $this->fetchAll($sql, $allParams);
    }

    public function getSummary(int $year): array
    {
        return $this->fetchAll(
            "SELECT supplement_type, COUNT(*) AS cnt, SUM(quantity) AS total_qty
             FROM (
                 SELECT supplement_type, quantity FROM dispensing_records WHERE YEAR(date_dispensed) = ?
                 UNION ALL
                 SELECT CASE WHEN dosage_iu = 100000 THEN '100,000 IU Vitamin A (Blue)' ELSE '200,000 IU Vitamin A (Red)' END, 1
                 FROM vitamin_a_records WHERE year = ?
                 UNION ALL
                 SELECT 'MNP (Micronutrient Powder)', 1 FROM mnp_records WHERE YEAR(date_given) = ?
                 UNION ALL
                 SELECT 'LNS-SQ (Lipid-based Nutrient Supplement)', 1 FROM lns_sq_records WHERE YEAR(date_given) = ?
             ) AS combined
             GROUP BY supplement_type
             ORDER BY cnt DESC",
            [$year, $year, $year, $year]
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
