<?php

namespace App\Models;

use Core\Model;
use App\Models\Beneficiary;

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
        // LNS-SQ is monthly; block re-distribution within 30 days
        return (bool) $this->fetch(
            "SELECT id FROM lns_sq_records WHERE beneficiary_id = ? AND date_given >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            [$beneficiaryId]
        );
    }

    public function getTotalRecords(int $beneficiaryId): int
    {
        $row = $this->fetch("SELECT COUNT(*) AS cnt FROM lns_sq_records WHERE beneficiary_id = ?", [$beneficiaryId]);
        return (int)($row['cnt'] ?? 0);
    }

    public function recordDistribution(array $data): int
    {
        if ($this->hasDuplicate((int)$data['beneficiary_id'], (int)$data['year'], $data['age_group'])) {
            throw new \RuntimeException('LNS-SQ was already given within the last 30 days. Minimum interval is 30 days.');
        }
        if ($this->getTotalRecords((int)$data['beneficiary_id']) >= 18) {
            throw new \RuntimeException('Maximum 18 monthly LNS-SQ supplies already given for this beneficiary.');
        }
        $row = [
            'beneficiary_id'      => $data['beneficiary_id'],
            'given_by'            => $data['given_by'] ?? null,
            'date_given'          => $data['date_given'],
            'year'                => $data['year'],
            'age_group'           => $data['age_group'],
            'completed_routine'   => (int)($data['completed_routine'] ?? 0),
            'sachets_distributed' => isset($data['sachets_distributed']) && $data['sachets_distributed'] !== null && $data['sachets_distributed'] !== ''
                ? (int)$data['sachets_distributed'] : 30,
            'weight_kg'           => isset($data['weight_kg']) && $data['weight_kg'] !== '' ? (float)$data['weight_kg'] : null,
            'notes'               => $data['notes'] ?? null,
        ];
        return $this->insert($row);
    }

    public function getCompletionByBarangay(int $year, string $barangay = ''): array
    {
        $extra = ''; $params = [$year];
        if ($barangay !== '') { $extra .= ' AND b.barangay = ?'; $params[] = $barangay; }
        return $this->fetchAll(
            "SELECT b.barangay, l.age_group, COUNT(*) AS total, SUM(l.completed_routine) AS completed
             FROM lns_sq_records l JOIN beneficiaries b ON b.id = l.beneficiary_id
             WHERE b.deleted_at IS NULL AND b.validation_status = 'validated' AND l.year = ?$extra
             GROUP BY b.barangay, l.age_group ORDER BY b.barangay, l.age_group",
            $params
        );
    }

    public function getAllByYear(int $year, string $barangay = '', string $purok = ''): array
    {
        $extra = ''; $params = [$year];
        if ($barangay !== '') { $extra .= ' AND b.barangay = ?';                 $params[] = $barangay; }
        if ($purok    !== '') { $extra .= ' AND LOWER(b.purok_zone) = LOWER(?)'; $params[] = $purok; }
        return $this->fetchAll(
            "SELECT l.*, b.last_name, b.first_name, b.barangay, b.date_of_birth,
                    u.full_name AS given_by_name
             FROM lns_sq_records l
             JOIN beneficiaries b ON b.id = l.beneficiary_id
             LEFT JOIN users u ON u.id = l.given_by
             WHERE b.deleted_at IS NULL AND b.validation_status = 'validated' AND l.year = ?$extra
             ORDER BY l.date_given DESC, b.barangay, b.last_name",
            $params
        );
    }

    public function getNotYetReceived(int $year, string $asOfDate = '', string $barangay = '', string $purok = ''): array
    {
        $ref = $asOfDate ?: date('Y-m-d');
        // params: age_months ref, age range ref, OPT year check, lns year check
        $extra = ''; $params = [$ref, $ref, $year, $year, $year];
        if ($barangay !== '') { $extra .= ' AND b.barangay = ?';                 $params[] = $barangay; }
        if ($purok    !== '') { $extra .= ' AND LOWER(b.purok_zone) = LOWER(?)'; $params[] = $purok; }
        return $this->fetchAll(
            "SELECT b.id, b.last_name, b.first_name, b.barangay, b.date_of_birth,
                    TIMESTAMPDIFF(MONTH, b.date_of_birth, ?) AS age_months
             FROM beneficiaries b
             WHERE b.deleted_at IS NULL AND b.validation_status = 'validated'
               AND " . Beneficiary::BNS_SUBMITTED_FILTER . "
               AND TIMESTAMPDIFF(MONTH, b.date_of_birth, ?) BETWEEN 6 AND 24
               AND EXISTS (
                   SELECT 1 FROM assessments a
                   WHERE a.beneficiary_id = b.id AND a.validation_status = 'validated'
                     AND a.assessment_year = ?
                     AND (a.nutritional_status IN ('SUW','UW')
                          OR a.wflh_status IN ('SW','MW')
                          OR a.hfa_status IN ('SSt','St'))
               )
               AND NOT EXISTS (
                   SELECT 1 FROM lns_sq_records l WHERE l.beneficiary_id = b.id AND l.year = ?
               )
               AND NOT EXISTS (
                   SELECT 1 FROM lns_recoveries lr WHERE lr.beneficiary_id = b.id AND lr.year = ?
               )$extra
             ORDER BY b.barangay, b.last_name",
            $params
        );
    }
}
