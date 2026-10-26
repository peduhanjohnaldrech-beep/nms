<?php

namespace App\Models;

use Core\Model;

class Beneficiary extends Model
{
    protected string $table = 'beneficiaries';

    // Reusable SQL snippet: exclude BNS mobile records still pending validation
    // Once a midwife validates, the record becomes visible regardless of submitted_at
    // Use with alias 'b' for beneficiaries table
    const BNS_SUBMITTED_FILTER = "NOT (b.source = 'Mobile' AND b.validation_status = 'pending' AND EXISTS (SELECT 1 FROM users u WHERE u.id = b.created_by AND LOWER(u.role) = 'bns'))";
    // Use when beneficiaries table has no alias
    const BNS_SUBMITTED_FILTER_NO_ALIAS = "NOT (source = 'Mobile' AND validation_status = 'pending' AND EXISTS (SELECT 1 FROM users u WHERE u.id = created_by AND LOWER(u.role) = 'bns'))";

    public function submitToAdmin(int $id, int $userId): void
    {
        $this->execute(
            "UPDATE beneficiaries SET submitted_at = NOW(), submitted_by = ? WHERE id = ?",
            [$userId, $id]
        );
    }

    public function search(string $term = '', string $barangay = '', int $page = 1, int $perPage = 25, string $source = '', string $ageStatus = '', string $role = '', string $purok = ''): array
    {
        $conditions = [
            'b.deleted_at IS NULL',
            "b.validation_status = 'validated'",
            // BNS-added mobile records only appear after BNS explicitly submits them
            self::BNS_SUBMITTED_FILTER,
        ];
        $params     = [];

        if ($term !== '') {
            $conditions[] = "(b.last_name LIKE ? OR b.first_name LIKE ? OR b.middle_name LIKE ?)";
            $like = '%' . $term . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        if ($barangay !== '') { $conditions[] = "b.barangay = ?";              $params[] = $barangay; }
        if ($purok    !== '') { $conditions[] = "LOWER(b.purok_zone) = LOWER(?)"; $params[] = $purok; }
        if ($source !== '') {
            if ($source === 'Excel') {
                $conditions[] = "b.source IN ('Excel', 'Excel Import')";
            } else {
                $conditions[] = "b.source = ?";
                $params[] = $source;
            }
        }

        if ($ageStatus === 'active')   { $conditions[] = "TIMESTAMPDIFF(MONTH, b.date_of_birth, CURDATE()) <= 59"; }
        if ($ageStatus === 'aged_out') { $conditions[] = "TIMESTAMPDIFF(MONTH, b.date_of_birth, CURDATE()) > 59"; }
        if ($ageStatus === '0_23')     { $conditions[] = "TIMESTAMPDIFF(MONTH, b.date_of_birth, CURDATE()) BETWEEN 0 AND 23"; }
        if ($ageStatus === '24_59')    { $conditions[] = "TIMESTAMPDIFF(MONTH, b.date_of_birth, CURDATE()) BETWEEN 24 AND 59"; }
        if ($ageStatus === 'recovered') {
            $conditions[] = "(SELECT nutritional_status FROM assessments WHERE beneficiary_id = b.id ORDER BY assessment_date DESC LIMIT 1) = 'Normal'";
            $conditions[] = "EXISTS (SELECT 1 FROM assessments WHERE beneficiary_id = b.id AND nutritional_status IN ('SUW','UW'))";
        }

        $where  = 'WHERE ' . implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM beneficiaries b $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $rows = $this->fetchAll(
            "SELECT b.*,
                    (SELECT MAX(assessment_date) FROM assessments WHERE beneficiary_id = b.id) AS last_assessed,
                    (SELECT nutritional_status FROM assessments WHERE beneficiary_id = b.id ORDER BY assessment_date DESC LIMIT 1) AS latest_status,
                    CASE WHEN (SELECT nutritional_status FROM assessments WHERE beneficiary_id = b.id ORDER BY assessment_date DESC LIMIT 1) = 'Normal'
                         AND EXISTS (SELECT 1 FROM assessments WHERE beneficiary_id = b.id AND nutritional_status IN ('SUW','UW'))
                         THEN 1 ELSE 0 END AS is_recovered
             FROM beneficiaries b $where ORDER BY b.last_name, b.first_name LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return [
            'rows'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
        ];
    }

    public function findByBarangay(string $barangay): array
    {
        return $this->fetchAll(
            "SELECT * FROM beneficiaries WHERE barangay = ? AND deleted_at IS NULL ORDER BY last_name, first_name",
            [$barangay]
        );
    }

    public function softDelete(int $id): int
    {
        return $this->execute("UPDATE beneficiaries SET deleted_at = NOW() WHERE id = ?", [$id]);
    }

    public function findDuplicates(string $lastName, string $firstName, string $dob, string $barangay): array
    {
        return $this->fetchAll(
            "SELECT * FROM beneficiaries
             WHERE last_name = ? AND first_name = ? AND date_of_birth = ? AND barangay = ?
             AND deleted_at IS NULL LIMIT 5",
            [$lastName, $firstName, $dob, $barangay]
        );
    }

    public function findDuplicatesByNameDob(string $lastName, string $firstName, string $dob): array
    {
        return $this->fetchAll(
            "SELECT * FROM beneficiaries
             WHERE last_name = ? AND first_name = ? AND date_of_birth = ?
             AND deleted_at IS NULL LIMIT 5",
            [$lastName, $firstName, $dob]
        );
    }

    public function getAllBarangays(): array
    {
        $db = \Core\Database::getInstance();

        // From managed barangays table (may not exist yet on older installs)
        try {
            $fromTable = $db->query("SELECT name FROM barangays ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            $fromTable = [];
        }

        // Fallback: config file (used if table is empty or missing)
        if (empty($fromTable)) {
            $fromTable = require BASE_PATH . '/config/barangays.php';
        }

        // Also pull any barangay values already in beneficiaries that aren't in the table
        $fromBenef = $this->fetchAll(
            "SELECT DISTINCT barangay FROM beneficiaries WHERE deleted_at IS NULL ORDER BY barangay"
        );

        $merged = array_unique(array_merge($fromTable, array_column($fromBenef, 'barangay')));
        $merged = array_filter($merged, fn($b) => !empty(trim((string)$b)));
        sort($merged);
        return array_map(fn($b) => ['barangay' => $b], array_values($merged));
    }

    /** Returns distinct non-empty puroks for a given barangay (or all if empty). */
    public function getPuroksByBarangay(string $barangay = ''): array
    {
        $where  = "deleted_at IS NULL AND purok_zone IS NOT NULL AND purok_zone <> ''";
        $params = [];
        if ($barangay !== '') { $where .= ' AND barangay = ?'; $params[] = $barangay; }
        $rows = $this->fetchAll(
            "SELECT DISTINCT purok_zone FROM beneficiaries WHERE $where ORDER BY purok_zone",
            $params
        );
        return array_column($rows, 'purok_zone');
    }
}
