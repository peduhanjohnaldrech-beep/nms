<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;
use App\Models\ProgramEnrollment;

class ValidationApiController extends ApiController
{
    /**
     * GET /api/validation/pending
     * Midwife: see all assessments with validation_status = 'pending'
     */
    public function pending(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['midwife', 'nutritionist', 'admin']);

        $db = Database::getInstance();

        $where  = ["a.validation_status = 'pending'"];
        $params = [];

        // Midwife scoped to barangay if assigned
        if ($this->isMidwife() && $this->userBarangay()) {
            $where[]  = 'b.barangay = ?';
            $params[] = $this->userBarangay();
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT a.id, a.beneficiary_id, a.assessment_date, a.age_in_months,
                   a.weight_kg, a.height_cm, a.nutritional_status, a.period,
                   a.assessment_year, a.assessed_by, a.validation_status,
                   a.rejection_note, a.created_at,
                   b.last_name, b.first_name, b.barangay, b.sex, b.date_of_birth,
                   u.full_name AS submitted_by_name
            FROM assessments a
            JOIN beneficiaries b ON b.id = a.beneficiary_id
            LEFT JOIN users u ON u.id = a.created_by
            $whereClause
            ORDER BY a.created_at DESC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->success(['assessments' => $rows, 'count' => count($rows)]);
    }

    /**
     * GET /api/validation/my-submissions
     * BNS sees their own submitted assessments and their validation status
     */
    public function mySubmissions(): void
    {
        $this->requireApiAuth();

        $db     = Database::getInstance();
        $userId = $this->userId();

        $where  = ['a.created_by = ?'];
        $params = [$userId];

        if ($status = $_GET['status'] ?? null) {
            $where[]  = 'a.validation_status = ?';
            $params[] = $status;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT a.id, a.beneficiary_id, a.assessment_date, a.age_in_months,
                   a.weight_kg, a.height_cm, a.nutritional_status, a.period,
                   a.assessment_year, a.validation_status, a.rejection_note,
                   a.validated_at, a.created_at,
                   b.last_name, b.first_name, b.barangay,
                   u.full_name AS validated_by_name
            FROM assessments a
            JOIN beneficiaries b ON b.id = a.beneficiary_id
            LEFT JOIN users u ON u.id = a.validated_by
            $whereClause
            ORDER BY a.created_at DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->success(['assessments' => $rows]);
    }

    /**
     * POST /api/validation/{id}/validate
     * Midwife approves an assessment
     */
    public function validate(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['midwife', 'nutritionist', 'admin']);

        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT a.*, b.barangay FROM assessments a
            JOIN beneficiaries b ON b.id = a.beneficiary_id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) $this->error('Assessment not found', 404);
        if ($row['validation_status'] !== 'pending') {
            $this->error('Assessment is not in pending status', 400);
        }
        if ($this->isMidwife() && $this->userBarangay() && $row['barangay'] !== $this->userBarangay()) {
            $this->error('Access denied', 403);
        }

        $db->prepare("
            UPDATE assessments
            SET validation_status = 'validated',
                validated_by = ?,
                validated_at = NOW(),
                rejection_note = NULL
            WHERE id = ?
        ")->execute([$this->userId(), $id]);

        (new ProgramEnrollment())->autoEnrollDSP((int)$id);

        $this->success([], 'Assessment validated');
    }

    /**
     * POST /api/validation/{id}/reject
     * Midwife rejects an assessment with a note
     */
    public function reject(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['midwife', 'nutritionist', 'admin']);

        $body = $this->body();
        $note = trim($body['note'] ?? '');

        if (!$note) $this->error('Rejection note is required', 422);

        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT a.*, b.barangay FROM assessments a
            JOIN beneficiaries b ON b.id = a.beneficiary_id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) $this->error('Assessment not found', 404);
        if ($row['validation_status'] !== 'pending') {
            $this->error('Assessment is not in pending status', 400);
        }
        if ($this->isMidwife() && $this->userBarangay() && $row['barangay'] !== $this->userBarangay()) {
            $this->error('Access denied', 403);
        }

        $db->prepare("
            UPDATE assessments
            SET validation_status = 'rejected',
                validated_by = ?,
                validated_at = NOW(),
                rejection_note = ?
            WHERE id = ?
        ")->execute([$this->userId(), $note, $id]);

        $this->success([], 'Assessment rejected');
    }

    /**
     * GET /api/validation/beneficiaries/pending
     * Midwife: see all beneficiaries with validation_status = 'pending'
     */
    public function beneficiariesPending(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['midwife', 'nutritionist', 'admin']);

        $db = Database::getInstance();

        $where  = ["b.validation_status = 'pending'", 'b.deleted_at IS NULL'];
        $params = [];

        if ($this->isMidwife() && $this->userBarangay()) {
            $where[]  = 'b.barangay = ?';
            $params[] = $this->userBarangay();
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT b.id, b.last_name, b.first_name, b.middle_name, b.suffix,
                   b.date_of_birth, b.sex, b.barangay, b.purok_zone,
                   b.validation_status, b.rejection_note, b.created_at,
                   u.full_name AS submitted_by_name
            FROM beneficiaries b
            LEFT JOIN users u ON u.id = b.created_by
            $whereClause
            ORDER BY b.created_at DESC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->success(['beneficiaries' => $rows, 'count' => count($rows)]);
    }

    /**
     * GET /api/validation/beneficiaries/my-submissions
     * BHW/encoder sees their own submitted beneficiaries and validation status
     */
    public function myBeneficiarySubmissions(): void
    {
        $this->requireApiAuth();

        $db     = Database::getInstance();
        $userId = $this->userId();

        $where  = ['b.created_by = ?', 'b.deleted_at IS NULL'];
        $params = [$userId];

        if ($status = $_GET['status'] ?? null) {
            $where[]  = 'b.validation_status = ?';
            $params[] = $status;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT b.id, b.last_name, b.first_name, b.middle_name, b.suffix,
                   b.date_of_birth, b.sex, b.barangay, b.purok_zone,
                   b.validation_status, b.rejection_note, b.validated_at, b.created_at,
                   u.full_name AS validated_by_name
            FROM beneficiaries b
            LEFT JOIN users u ON u.id = b.validated_by
            $whereClause
            ORDER BY b.created_at DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->success(['beneficiaries' => $rows]);
    }

    /**
     * POST /api/validation/beneficiaries/{id}/validate
     * Midwife approves a beneficiary registration
     */
    public function validateBeneficiary(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['midwife', 'nutritionist', 'admin']);

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM beneficiaries WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) $this->error('Beneficiary not found', 404);
        if ($row['validation_status'] !== 'pending') {
            $this->error('Beneficiary is not in pending status', 400);
        }
        if ($this->isMidwife() && $this->userBarangay() && $row['barangay'] !== $this->userBarangay()) {
            $this->error('Access denied', 403);
        }

        $db->prepare("
            UPDATE beneficiaries
            SET validation_status = 'validated',
                validated_by      = ?,
                validated_at      = NOW(),
                rejection_note    = NULL
            WHERE id = ?
        ")->execute([$this->userId(), $id]);

        $this->success([], 'Beneficiary validated');
    }

    /**
     * POST /api/validation/beneficiaries/{id}/reject
     * Midwife rejects a beneficiary registration with a note
     */
    public function rejectBeneficiary(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['midwife', 'nutritionist', 'admin']);

        $body = $this->body();
        $note = trim($body['note'] ?? '');
        if (!$note) $this->error('Rejection note is required', 422);

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM beneficiaries WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) $this->error('Beneficiary not found', 404);
        if ($row['validation_status'] !== 'pending') {
            $this->error('Beneficiary is not in pending status', 400);
        }
        if ($this->isMidwife() && $this->userBarangay() && $row['barangay'] !== $this->userBarangay()) {
            $this->error('Access denied', 403);
        }

        $db->prepare("
            UPDATE beneficiaries
            SET validation_status = 'rejected',
                validated_by      = ?,
                validated_at      = NOW(),
                rejection_note    = ?
            WHERE id = ?
        ")->execute([$this->userId(), $note, $id]);

        $this->success([], 'Beneficiary rejected');
    }

    /**
     * POST /api/validation/batch
     * Batch validate assessments or beneficiaries.
     * Body: { "type": "assessment"|"beneficiary", "ids": [1,2,3] }
     */
    public function batch(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['midwife', 'nutritionist', 'admin']);

        $body = $this->body();
        $type = $body['type'] ?? '';
        $ids  = array_filter(array_map('intval', $body['ids'] ?? []), fn($id) => $id > 0);

        if (!in_array($type, ['assessment', 'beneficiary'])) {
            $this->error('type must be assessment or beneficiary', 422);
        }
        if (empty($ids)) {
            $this->error('ids array is required', 422);
        }

        $db   = Database::getInstance();
        $uid  = $this->userId();
        $brgy = $this->userBarangay();
        $validated = 0;
        $skipped   = 0;

        if ($type === 'assessment') {
            foreach ($ids as $id) {
                $stmt = $db->prepare("
                    SELECT a.validation_status, b.barangay FROM assessments a
                    JOIN beneficiaries b ON b.id = a.beneficiary_id
                    WHERE a.id = ?
                ");
                $stmt->execute([$id]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$row || $row['validation_status'] !== 'pending') { $skipped++; continue; }
                if ($this->isMidwife() && $brgy && $row['barangay'] !== $brgy) { $skipped++; continue; }
                $db->prepare("
                    UPDATE assessments
                    SET validation_status='validated', validated_by=?, validated_at=NOW(), rejection_note=NULL
                    WHERE id=?
                ")->execute([$uid, $id]);
                (new ProgramEnrollment())->autoEnrollDSP($id);
                $validated++;
            }
        } else {
            foreach ($ids as $id) {
                $stmt = $db->prepare("SELECT validation_status, barangay FROM beneficiaries WHERE id=? AND deleted_at IS NULL");
                $stmt->execute([$id]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$row || $row['validation_status'] !== 'pending') { $skipped++; continue; }
                if ($this->isMidwife() && $brgy && $row['barangay'] !== $brgy) { $skipped++; continue; }
                $db->prepare("
                    UPDATE beneficiaries
                    SET validation_status='validated', validated_by=?, validated_at=NOW(), rejection_note=NULL
                    WHERE id=?
                ")->execute([$uid, $id]);
                $validated++;
            }
        }

        $this->success(['validated' => $validated, 'skipped' => $skipped],
            "$validated record(s) validated" . ($skipped ? ", $skipped skipped" : ''));
    }

    /**
     * GET /api/validation/counts
     * Returns counts by status — useful for dashboard badges
     */
    public function counts(): void
    {
        $this->requireApiAuth();

        $db     = Database::getInstance();
        $userId = $this->userId();
        $role   = strtolower($this->apiUser['role'] ?? '');

        if (in_array($role, ['midwife', 'nutritionist', 'admin'])) {
            // Pending count for midwife/admin
            $where  = [];
            $params = [];
            if ($this->isMidwife() && $this->userBarangay()) {
                $where[]  = 'b.barangay = ?';
                $params[] = $this->userBarangay();
            }
            $wc = $where ? 'AND ' . implode(' AND ', $where) : '';
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM assessments a
                JOIN beneficiaries b ON b.id = a.beneficiary_id
                WHERE a.validation_status = 'pending' $wc
            ");
            $stmt->execute($params);
            $this->success(['pending' => (int)$stmt->fetchColumn()]);
        } else {
            // BNS: counts of their own submissions
            $stmt = $db->prepare("
                SELECT validation_status, COUNT(*) as cnt
                FROM assessments WHERE created_by = ?
                GROUP BY validation_status
            ");
            $stmt->execute([$userId]);
            $counts = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                $counts[$r['validation_status']] = (int)$r['cnt'];
            }
            $this->success($counts);
        }
    }
}
