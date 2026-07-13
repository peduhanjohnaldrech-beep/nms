<?php

namespace App\Models;

use Core\Model;

class Beneficiary extends Model
{
    protected string $table = 'beneficiaries';

    public function submitToAdmin(int $id, int $userId): void
    {
        $this->execute(
            "UPDATE beneficiaries SET submitted_at = NOW(), submitted_by = ? WHERE id = ?",
            [$userId, $id]
        );
    }

    public function search(string $term = '', string $barangay = '', int $page = 1, int $perPage = 25, string $source = '', string $ageStatus = '', string $role = ''): array
    {
        $conditions = ['b.deleted_at IS NULL'];
        $params     = [];

        // Admin/nutritionist only see submitted records
        if (in_array($role, ['admin', 'nutritionist'])) {
            $conditions[] = 'b.submitted_at IS NOT NULL';
        }

        if ($term !== '') {
            $conditions[] = "(b.last_name LIKE ? OR b.first_name LIKE ? OR b.middle_name LIKE ?)";
            $like = '%' . $term . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }
        if ($barangay !== '') { $conditions[] = "b.barangay = ?"; $params[] = $barangay; }
        if ($source !== '') {
            if ($source === 'Excel') {
                $conditions[] = "b.source IN ('Excel', 'Excel Import')";
            } else {
                $conditions[] = "b.source = ?";
                $params[] = $source;
            }
        }

        $cutoff = date('Y-m-d', strtotime('-59 months'));
        if ($ageStatus === 'active')   { $conditions[] = "b.date_of_birth >= ?"; $params[] = $cutoff; }
        if ($ageStatus === 'aged_out') { $conditions[] = "b.date_of_birth < ?";  $params[] = $cutoff; }
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
        $config  = require BASE_PATH . '/config/barangays.php';
        $fromDb  = $this->fetchAll(
            "SELECT DISTINCT barangay FROM beneficiaries WHERE deleted_at IS NULL ORDER BY barangay"
        );
        $merged  = array_unique(array_merge($config, array_column($fromDb, 'barangay')));
        sort($merged);
        return array_map(fn($b) => ['barangay' => $b], $merged);
    }
}
