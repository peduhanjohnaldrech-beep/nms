<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;
use App\Models\Beneficiary;

class BeneficiaryApiController extends ApiController
{
    /**
     * GET /api/beneficiaries
     * Query: ?barangay=&search=&page=1&per_page=50&updated_since=
     *
     * BHW role is restricted to their assigned barangay.
     */
    public function index(): void
    {
        $this->requireApiAuth();

        $barangay    = $_GET['barangay'] ?? null;
        $search      = $_GET['search'] ?? '';
        $page        = max(1, (int)($_GET['page'] ?? 1));
        $perPage     = min(200, max(10, (int)($_GET['per_page'] ?? 50)));
        $updatedSince = $_GET['updated_since'] ?? null;

        // BHW / BNS can only see their own barangay
        if ($this->isBhw() || $this->isBns()) {
            $barangay = $this->userBarangay();
        }

        $db = Database::getInstance();

        $role   = strtolower($this->apiUser['role'] ?? '');
        $where  = ['b.deleted_at IS NULL'];
        $params = [];

        // Admin / Nutritionist only see beneficiaries submitted by BNS
        if (in_array($role, ['admin', 'nutritionist'])) {
            $where[] = 'b.submitted_at IS NOT NULL';
        }

        if ($barangay) {
            $where[]  = 'b.barangay = ?';
            $params[] = $barangay;
        }
        if ($search) {
            $where[]  = '(b.last_name LIKE ? OR b.first_name LIKE ? OR b.middle_name LIKE ?)';
            $s = '%' . $search . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }
        if ($updatedSince) {
            $where[]  = 'b.updated_at > ?';
            $params[] = $updatedSince;
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $total = $db->prepare("SELECT COUNT(*) FROM beneficiaries b WHERE $whereClause");
        $total->execute($params);
        $totalCount = (int)$total->fetchColumn();

        $stmt = $db->prepare("
            SELECT b.*
            FROM beneficiaries b
            WHERE $whereClause
            ORDER BY b.last_name, b.first_name
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([...$params, $perPage, $offset]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->success([
            'beneficiaries' => $rows,
            'pagination'    => [
                'total'       => $totalCount,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => (int)ceil($totalCount / $perPage),
            ],
        ]);
    }

    /**
     * GET /api/beneficiaries/{id}
     */
    public function show(string $id): void
    {
        $this->requireApiAuth();

        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM beneficiaries WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            $this->error('Beneficiary not found', 404);
        }

        // BHW barangay restriction
        if ($this->isBhw() && $row['barangay'] !== $this->userBarangay()) {
            $this->error('Access denied', 403);
        }

        $this->success(['beneficiary' => $row]);
    }

    /**
     * POST /api/beneficiaries
     */
    public function store(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder', 'nutritionist', 'bhw', 'bns']);

        $data = $this->body();
        $errors = $this->validate($data);
        if ($errors) {
            $this->json(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
        }

        // BHW/BNS can only add to their barangay
        if ($this->isBhw() || $this->isBns()) {
            $data['barangay'] = $this->userBarangay();
        }

        $model = new Beneficiary();
        $id = $model->insert([
            'last_name'                => trim($data['last_name']),
            'first_name'               => trim($data['first_name']),
            'middle_name'              => trim($data['middle_name'] ?? ''),
            'suffix'                   => trim($data['suffix'] ?? ''),
            'date_of_birth'            => $data['date_of_birth'],
            'sex'                      => $data['sex'],
            'place_of_birth'           => $data['place_of_birth'] ?? null,
            'barangay'                 => $data['barangay'],
            'purok_zone'               => $data['purok_zone'] ?? null,
            'household_no'             => $data['household_no'] ?? null,
            'mother_name'              => $data['mother_name'] ?? null,
            'father_name'              => $data['father_name'] ?? null,
            'guardian_name'            => $data['guardian_name'] ?? null,
            'guardian_relationship'    => $data['guardian_relationship'] ?? null,
            'contact_number'           => $data['contact_number'] ?? null,
            'income_classification'    => $data['income_classification'] ?? null,
            'household_monthly_income' => $data['household_monthly_income'] ?? null,
            'philhealth_status'        => $data['philhealth_status'] ?? null,
            'is_4ps_member'            => (int)($data['is_4ps_member'] ?? 0),
            'is_pwd_household'         => (int)($data['is_pwd_household'] ?? 0),
            'is_indigenous_people'     => (int)($data['is_indigenous_people'] ?? 0),
            'ip_group'                 => $data['ip_group'] ?? null,
            'source'                   => $data['source'] ?? 'Mobile',
            'validation_status'        => $this->isFieldWorker() ? 'pending' : 'validated',
            'created_by'               => $this->userId(),
        ]);

        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM beneficiaries WHERE id = ?');
        $stmt->execute([$id]);
        $created = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->success(['beneficiary' => $created], 'Beneficiary created');
    }

    /**
     * PUT /api/beneficiaries/{id}
     */
    public function update(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder', 'nutritionist', 'bhw', 'bns']);

        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM beneficiaries WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing) {
            $this->error('Beneficiary not found', 404);
        }
        if ($this->isBhw() && $existing['barangay'] !== $this->userBarangay()) {
            $this->error('Access denied', 403);
        }

        $data   = $this->body();
        $errors = $this->validate($data, (int)$id);
        if ($errors) {
            $this->json(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
        }

        $allowed = [
            'last_name','first_name','middle_name','suffix','date_of_birth','sex',
            'place_of_birth','barangay','purok_zone','household_no','mother_name',
            'father_name','guardian_name','guardian_relationship','contact_number',
            'income_classification','household_monthly_income','philhealth_status',
            'is_4ps_member','is_pwd_household','is_indigenous_people','ip_group',
        ];

        $update = ['updated_at' => date('Y-m-d H:i:s')];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        // BHW cannot change barangay
        if ($this->isBhw()) {
            unset($update['barangay']);
        }

        // Field worker editing a rejected record = resubmit for validation
        if ($this->isFieldWorker() && ($existing['validation_status'] ?? '') === 'rejected') {
            $update['validation_status'] = 'pending';
            $update['rejection_note']    = null;
            $update['validated_by']      = null;
            $update['validated_at']      = null;
        }

        $model = new Beneficiary();
        $model->update((int)$id, $update);

        $stmt = $db->prepare('SELECT * FROM beneficiaries WHERE id = ?');
        $stmt->execute([$id]);
        $updated = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->success(['beneficiary' => $updated], 'Beneficiary updated');
    }

    /**
     * DELETE /api/beneficiaries/{id}  — soft delete
     */
    public function destroy(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','admin']);

        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM beneficiaries WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) $this->error('Beneficiary not found', 404);
        if ($this->isBhw() && $row['barangay'] !== $this->userBarangay()) $this->error('Access denied', 403);

        $db->prepare('UPDATE beneficiaries SET deleted_at = ? WHERE id = ?')
           ->execute([date('Y-m-d H:i:s'), $id]);
        $this->success([], 'Beneficiary moved to trash');
    }

    /**
     * GET /api/beneficiaries/trash
     */
    public function trash(): void
    {
        $this->requireApiAuth();
        $db    = Database::getInstance();
        $where = ['deleted_at IS NOT NULL'];
        $params = [];
        if ($this->isBhw()) { $where[] = 'barangay = ?'; $params[] = $this->userBarangay(); }
        $stmt = $db->prepare('SELECT * FROM beneficiaries WHERE ' . implode(' AND ', $where) . ' ORDER BY deleted_at DESC');
        $stmt->execute($params);
        $this->success(['beneficiaries' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    /**
     * POST /api/beneficiaries/{id}/restore
     */
    public function restore(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','admin']);
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM beneficiaries WHERE id = ? AND deleted_at IS NOT NULL');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) $this->error('Not found in trash', 404);
        $db->prepare('UPDATE beneficiaries SET deleted_at = NULL WHERE id = ?')->execute([$id]);
        $this->success([], 'Beneficiary restored');
    }

    /**
     * GET /api/beneficiaries/followup
     */
    public function followup(): void
    {
        $this->requireApiAuth();
        $db    = Database::getInstance();
        $brgy  = $this->isBhw() ? $this->userBarangay() : ($_GET['barangay'] ?? null);
        $year  = (int)($_GET['year'] ?? date('Y'));
        $where  = ["b.deleted_at IS NULL", "a.assessment_year = ?", "a.nutritional_status IN ('SUW','UW')"];
        $params = [$year];
        if ($brgy) { $where[] = 'b.barangay = ?'; $params[] = $brgy; }
        $stmt = $db->prepare("
            SELECT b.id, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.sex,
                   b.contact_number, b.mother_name,
                   a.nutritional_status, a.weight_kg, a.assessment_date, a.period
            FROM beneficiaries b
            JOIN assessments a ON a.id = (
                SELECT id FROM assessments WHERE beneficiary_id = b.id ORDER BY assessment_date DESC LIMIT 1
            )
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.nutritional_status DESC, b.last_name
        ");
        $stmt->execute($params);
        $this->success(['beneficiaries' => $stmt->fetchAll(\PDO::FETCH_ASSOC), 'year' => $year]);
    }

    /**
     * GET /api/beneficiaries/check-duplicate
     */
    public function checkDuplicate(): void
    {
        $this->requireApiAuth();
        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT id, last_name, first_name, date_of_birth, barangay
            FROM beneficiaries WHERE deleted_at IS NULL
            AND LOWER(first_name)=LOWER(?) AND LOWER(last_name)=LOWER(?) AND date_of_birth=?
        ");
        $stmt->execute([$_GET['first_name'] ?? '', $_GET['last_name'] ?? '', $_GET['date_of_birth'] ?? '']);
        $matches = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->success(['duplicates' => $matches, 'has_duplicate' => count($matches) > 0]);
    }

    /**
     * GET /api/beneficiaries/ready-to-submit
     * BNS fetches their validated, not-yet-submitted beneficiaries.
     */
    public function readyToSubmit(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['bns']);

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, last_name, first_name, middle_name, date_of_birth, sex, barangay, validation_status, submitted_at
             FROM beneficiaries
             WHERE deleted_at IS NULL
               AND barangay = ?
               AND validation_status = 'validated'
               AND submitted_at IS NULL
             ORDER BY last_name, first_name"
        );
        $stmt->execute([$this->userBarangay()]);
        $this->success(['beneficiaries' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    /**
     * POST /api/beneficiaries/batch-submit
     * BNS submits multiple validated beneficiaries to admin at once.
     */
    public function batchSubmitToAdmin(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['bns']);

        $ids = array_filter(array_map('intval', $this->body()['ids'] ?? []), fn($id) => $id > 0);
        if (empty($ids)) $this->error('No IDs provided', 422);

        $db        = Database::getInstance();
        $barangay  = $this->userBarangay();
        $userId    = $this->userId();
        $submitted = 0;
        $skipped   = 0;

        foreach ($ids as $id) {
            $stmt = $db->prepare('SELECT * FROM beneficiaries WHERE id = ? AND deleted_at IS NULL');
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row || $row['barangay'] !== $barangay
                || ($row['validation_status'] ?? '') !== 'validated'
                || !empty($row['submitted_at'])) {
                $skipped++;
                continue;
            }

            $db->prepare('UPDATE beneficiaries SET submitted_at = NOW(), submitted_by = ? WHERE id = ?')
               ->execute([$userId, $id]);
            $submitted++;
        }

        $this->success(
            ['submitted' => $submitted, 'skipped' => $skipped],
            "$submitted beneficiar" . ($submitted === 1 ? 'y' : 'ies') . " submitted to admin"
        );
    }

    /**
     * POST /api/beneficiaries/{id}/submit
     * BNS submits a validated beneficiary to admin.
     */
    public function submitToAdmin(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['bns']);

        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM beneficiaries WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) $this->error('Beneficiary not found', 404);
        if ($row['barangay'] !== $this->userBarangay()) $this->error('Access denied', 403);
        if (($row['validation_status'] ?? '') !== 'validated') $this->error('Beneficiary must be validated before submitting', 422);
        if (!empty($row['submitted_at'])) $this->error('Already submitted', 422);

        $db->prepare('UPDATE beneficiaries SET submitted_at = NOW(), submitted_by = ? WHERE id = ?')
           ->execute([$this->userId(), $id]);

        $this->success([], 'Beneficiary submitted to admin successfully');
    }

    // -------------------------------------------------------
    private function validate(array $data, int $excludeId = 0): array
    {
        $errors = [];
        if (empty($data['last_name']))    $errors[] = 'last_name is required';
        if (empty($data['first_name']))   $errors[] = 'first_name is required';
        if (empty($data['date_of_birth'])) $errors[] = 'date_of_birth is required';
        if (empty($data['sex']) || !in_array($data['sex'], ['Male','Female']))
            $errors[] = 'sex must be Male or Female';
        if (empty($data['barangay']))     $errors[] = 'barangay is required';
        return $errors;
    }
}
