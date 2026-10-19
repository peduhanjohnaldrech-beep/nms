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

        $barangay      = $_GET['barangay'] ?? null;
        $purok         = $_GET['purok'] ?? null;
        $search        = $_GET['search'] ?? '';
        $page          = max(1, (int)($_GET['page'] ?? 1));
        $perPage       = min(500, max(10, (int)($_GET['per_page'] ?? 50)));
        $updatedSince  = $_GET['updated_since'] ?? null;
        $validatedOnly = !empty($_GET['validated_only']);
        $activeOnly    = !empty($_GET['active_only']);
        $excludePeriod = $_GET['exclude_period'] ?? null;
        $excludeYear   = isset($_GET['exclude_year']) ? (int)$_GET['exclude_year'] : null;

        // BHW / BNS can only see their own barangay
        if ($this->isBhw() || $this->isBns()) {
            $barangay = $this->userBarangay();
        }

        $db = Database::getInstance();

        $role   = strtolower($this->apiUser['role'] ?? '');
        $where  = ['b.deleted_at IS NULL'];
        $params = [];

        // Admin / Nutritionist see validated and pending records
        if (in_array($role, ['admin', 'nutritionist'])) {
            $where[] = "b.validation_status IN ('pending', 'validated')";
        }

        if ($barangay) {
            $where[]  = 'b.barangay = ?';
            $params[] = $barangay;
        }
        if ($purok) {
            $where[]  = 'LOWER(b.purok_zone) = LOWER(?)';
            $params[] = $purok;
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

        // Only show validated beneficiaries (for OPT batch, etc.)
        if ($validatedOnly) {
            $where[] = "b.validation_status = 'validated'";
        }

        // Only show active (0–59 months) beneficiaries
        if ($activeOnly) {
            $where[] = "TIMESTAMPDIFF(MONTH, b.date_of_birth, CURDATE()) <= 59";
        }

        // Exclude beneficiaries who already have an assessment for this period/year
        if ($excludePeriod && $excludeYear) {
            $where[]  = "NOT EXISTS (
                SELECT 1 FROM assessments a
                WHERE a.beneficiary_id = b.id
                  AND a.period = ?
                  AND a.assessment_year = ?
                  AND a.validation_status != 'rejected'
            )";
            $params[] = $excludePeriod;
            $params[] = $excludeYear;
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
        $this->requireRole(['encoder', 'nutritionist', 'bhw', 'bns', 'midwife']);

        $data = $this->body();
        $errors = $this->validate($data);
        if ($errors) {
            $this->json(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
        }

        // Block beneficiaries over 59 months old
        if (!empty($data['date_of_birth'])) {
            $dob = new \DateTime($data['date_of_birth']);
            $now = new \DateTime();
            $ageInMonths = ($now->format('Y') - $dob->format('Y')) * 12 + ($now->format('n') - $dob->format('n'));
            if ($ageInMonths > 59) {
                $this->error('Beneficiary is over 59 months old and cannot be registered', 422);
            }
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

        $this->logActivity('beneficiary_create', "Added beneficiary ID $id: {$data['last_name']}, {$data['first_name']}");
        $this->success(['beneficiary' => $created], 'Beneficiary created');
    }

    /**
     * PUT /api/beneficiaries/{id}
     */
    public function update(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder', 'nutritionist', 'bhw', 'bns', 'midwife']);

        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM beneficiaries WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing) {
            $this->error('Beneficiary not found', 404);
        }
        if (($this->isBhw() || $this->isBns()) &&
            strtolower(trim($existing['barangay'] ?? '')) !== strtolower(trim($this->userBarangay() ?? ''))) {
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

        $this->logActivity('beneficiary_update', "Updated beneficiary ID $id: {$data['last_name']}, {$data['first_name']}");
        $this->success(['beneficiary' => $updated], 'Beneficiary updated');
    }

    /**
     * DELETE /api/beneficiaries/{id}  — soft delete
     */
    public function destroy(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','admin','bns','bhw','midwife']);

        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM beneficiaries WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) $this->error('Beneficiary not found', 404);
        $userBrgy = strtolower(trim($this->userBarangay() ?? ''));
        $rowBrgy  = strtolower(trim($row['barangay'] ?? ''));
        if (($this->isBhw() || $this->isBns()) && $rowBrgy !== $userBrgy) $this->error('Access denied', 403);

        $db->prepare('UPDATE beneficiaries SET deleted_at = ? WHERE id = ?')
           ->execute([date('Y-m-d H:i:s'), $id]);
        $this->logActivity('beneficiary_delete', "Deleted beneficiary ID $id");
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
     *
     * Returns children needing follow-up:
     *   1. Underweight / severely underweight by WFA (SUW, UW) in latest assessment for the year
     *   2. Wasted / severely wasted by WFL/H (SW, MW) in latest assessment for the year
     *   3. Actively enrolled in DSP (regardless of current assessment status)
     * UNION deduplicates children who appear in multiple categories.
     */
    public function followup(): void
    {
        $this->requireApiAuth();
        $db    = Database::getInstance();
        $brgy  = $this->isBhw() ? $this->userBarangay() : ($_GET['barangay'] ?? null);
        $year  = (int)($_GET['year'] ?? date('Y'));

        $brgyWhere  = $brgy ? 'AND b.barangay = ?' : '';
        $brgyParams = $brgy ? [$brgy] : [];

        $baseSelect = "SELECT b.id, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.sex,
                   b.contact_number, b.mother_name,
                   a.nutritional_status, a.wflh_status, a.weight_kg, a.assessment_date, a.period,
                   0 AS is_dsp, NULL AS dsp_enrollment_date,
                   DATEDIFF(CURDATE(), a.assessment_date) AS days_since_assessment";
        $baseFrom = "FROM beneficiaries b
            JOIN assessments a ON a.id = (
                SELECT id FROM assessments WHERE beneficiary_id = b.id
                AND validation_status = 'validated'
                ORDER BY assessment_date DESC LIMIT 1
            )
            WHERE b.deleted_at IS NULL AND b.validation_status = 'validated'
            AND a.assessment_year = ?";

        $stmt = $db->prepare("
            $baseSelect
            $baseFrom AND a.nutritional_status IN ('SUW','UW') $brgyWhere
            UNION
            $baseSelect
            $baseFrom AND a.wflh_status IN ('SW','MW') $brgyWhere
            UNION
            SELECT b.id, b.last_name, b.first_name, b.barangay, b.date_of_birth, b.sex,
                   b.contact_number, b.mother_name,
                   a.nutritional_status, a.wflh_status, a.weight_kg, a.assessment_date, a.period,
                   1 AS is_dsp, pe.enrollment_date AS dsp_enrollment_date,
                   DATEDIFF(CURDATE(), a.assessment_date) AS days_since_assessment
            FROM beneficiaries b
            JOIN program_enrollments pe ON pe.beneficiary_id = b.id
                AND pe.program = 'DSP' AND pe.status = 'Active'
                AND pe.enrollment_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
            LEFT JOIN assessments a ON a.id = (
                SELECT id FROM assessments WHERE beneficiary_id = b.id
                AND validation_status = 'validated'
                ORDER BY assessment_date DESC LIMIT 1
            )
            WHERE b.deleted_at IS NULL AND b.validation_status = 'validated' $brgyWhere
            ORDER BY nutritional_status DESC, last_name
        ");
        $stmt->execute(array_merge([$year], $brgyParams, [$year], $brgyParams, $brgyParams));
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
     * Fetch validated, not-yet-submitted beneficiaries scoped to caller's barangay.
     * Allowed: bns, midwife
     */
    public function readyToSubmit(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['bns', 'midwife']);

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
     * Submit multiple validated beneficiaries to admin.
     * Allowed: bns, midwife (both scoped to their barangay)
     */
    public function batchSubmitToAdmin(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['bns', 'midwife']);

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

            if (!$row || strtolower(trim($row['barangay'] ?? '')) !== strtolower(trim($barangay ?? ''))
                || ($row['validation_status'] ?? '') !== 'validated'
                || !empty($row['submitted_at'])) {
                $skipped++;
                continue;
            }

            $db->prepare('UPDATE beneficiaries SET submitted_at = NOW(), submitted_by = ?, updated_at = NOW() WHERE id = ?')
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

        $db->prepare('UPDATE beneficiaries SET submitted_at = NOW(), submitted_by = ?, updated_at = NOW() WHERE id = ?')
           ->execute([$this->userId(), $id]);

        $this->success([], 'Beneficiary submitted to admin successfully');
    }

    /**
     * GET /api/beneficiaries/{id}/programs
     * Returns all MNS, DSP, and assessment records for a beneficiary.
     */
    public function programs(string $id): void
    {
        $this->requireApiAuth();

        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT id, barangay FROM beneficiaries WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $bene = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$bene) { $this->error('Beneficiary not found', 404); }
        if ($this->isBhw() && $bene['barangay'] !== $this->userBarangay()) {
            $this->error('Access denied', 403);
        }

        // Vitamin A
        $vitaStmt = $db->prepare(
            'SELECT id, round, year, distribution_date, dosage_iu, capsule_color, delivery_mode
             FROM vitamin_a_records WHERE beneficiary_id = ? ORDER BY distribution_date DESC'
        );
        $vitaStmt->execute([$id]);
        $vitaRecords = $vitaStmt->fetchAll(\PDO::FETCH_ASSOC);

        // MNP
        $mnpStmt = $db->prepare(
            'SELECT id, year, date_given, age_group, sachets_distributed, completed_routine
             FROM mnp_records WHERE beneficiary_id = ? ORDER BY date_given DESC'
        );
        $mnpStmt->execute([$id]);
        $mnpRecords = $mnpStmt->fetchAll(\PDO::FETCH_ASSOC);

        // LNS-SQ
        $lnsStmt = $db->prepare(
            'SELECT id, year, date_given, age_group, sachets_distributed, completed_routine, weight_kg
             FROM lns_sq_records WHERE beneficiary_id = ? ORDER BY date_given DESC'
        );
        $lnsStmt->execute([$id]);
        $lnsRecords = $lnsStmt->fetchAll(\PDO::FETCH_ASSOC);

        // DSP enrollment
        $dspStmt = $db->prepare(
            "SELECT id, enrollment_date, end_date, status, cycle_year,
                    intervention_type, pre_weight_kg, post_weight_kg, notes
             FROM program_enrollments
             WHERE beneficiary_id = ? AND program = 'DSP'
             ORDER BY enrollment_date DESC"
        );
        $dspStmt->execute([$id]);
        $dspRecords = $dspStmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->success([
            'vitamin_a' => $vitaRecords,
            'mnp'       => $mnpRecords,
            'lns_sq'    => $lnsRecords,
            'dsp'       => $dspRecords,
        ]);
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
