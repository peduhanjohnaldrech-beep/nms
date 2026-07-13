<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;
use App\Models\Assessment;
use App\Models\Beneficiary;
use App\Models\ProgramEnrollment;

class AssessmentApiController extends ApiController
{
    /**
     * GET /api/assessments
     * Query: ?beneficiary_id=&barangay=&year=&period=&page=1&updated_since=
     */
    public function index(): void
    {
        $this->requireApiAuth();

        $beneficiaryId = $_GET['beneficiary_id'] ?? null;
        $barangay      = $_GET['barangay'] ?? null;
        $year          = $_GET['year'] ?? null;
        $period        = $_GET['period'] ?? null;
        $page          = max(1, (int)($_GET['page'] ?? 1));
        $perPage       = min(200, max(10, (int)($_GET['per_page'] ?? 100)));
        $updatedSince  = $_GET['updated_since'] ?? null;

        if ($this->isBhw()) {
            $barangay = $this->userBarangay();
        }

        $db     = Database::getInstance();
        $where  = [];
        $params = [];

        if ($beneficiaryId) {
            $where[]  = 'a.beneficiary_id = ?';
            $params[] = $beneficiaryId;
        }
        if ($barangay) {
            $where[]  = 'b.barangay = ?';
            $params[] = $barangay;
        }
        if ($year) {
            $where[]  = 'a.assessment_year = ?';
            $params[] = $year;
        }
        if ($period && in_array($period, ['January','July'])) {
            $where[]  = 'a.period = ?';
            $params[] = $period;
        }
        if ($updatedSince) {
            $where[]  = 'a.created_at > ?';
            $params[] = $updatedSince;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset      = ($page - 1) * $perPage;

        $total = $db->prepare("
            SELECT COUNT(*) FROM assessments a
            JOIN beneficiaries b ON b.id = a.beneficiary_id
            $whereClause
        ");
        $total->execute($params);
        $totalCount = (int)$total->fetchColumn();

        $stmt = $db->prepare("
            SELECT a.*, b.last_name, b.first_name, b.middle_name, b.barangay
            FROM assessments a
            JOIN beneficiaries b ON b.id = a.beneficiary_id
            $whereClause
            ORDER BY a.assessment_date DESC, a.id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([...$params, $perPage, $offset]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->success([
            'assessments' => $rows,
            'pagination'  => [
                'total'       => $totalCount,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => (int)ceil($totalCount / $perPage),
            ],
        ]);
    }

    /**
     * GET /api/assessments/{id}
     */
    public function show(string $id): void
    {
        $this->requireApiAuth();

        $db   = Database::getInstance();
        $stmt = $db->prepare('
            SELECT a.*, b.last_name, b.first_name, b.barangay
            FROM assessments a
            JOIN beneficiaries b ON b.id = a.beneficiary_id
            WHERE a.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            $this->error('Assessment not found', 404);
        }
        if ($this->isBhw() && $row['barangay'] !== $this->userBarangay()) {
            $this->error('Access denied', 403);
        }

        $this->success(['assessment' => $row]);
    }

    /**
     * POST /api/assessments
     * Body: { beneficiary_id, assessment_date, weight_kg, height_cm?, muac_cm?,
     *         period, assessment_year, assessed_by?, remarks? }
     */
    public function store(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder', 'nutritionist', 'bhw', 'bns']);

        $data   = $this->body();
        $errors = $this->validate($data);
        if ($errors) {
            $this->json(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 422);
        }

        // Verify beneficiary exists and BHW/BNS barangay restriction
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM beneficiaries WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$data['beneficiary_id']]);
        $bene = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$bene) {
            $this->error('Beneficiary not found', 404);
        }
        if (($this->isBhw() || $this->isBns()) && $bene['barangay'] !== $this->userBarangay()) {
            $this->error('Access denied', 403);
        }

        $role   = strtolower($this->apiUser['role'] ?? '');
        $autoValidated = in_array($role, ['admin', 'nutritionist']);

        $period = (int)date('n', strtotime($data['assessment_date'])) <= 6 ? 'January' : 'July';
        $year   = (int)date('Y', strtotime($data['assessment_date']));

        $model = new Assessment();
        try {
            $id = $model->createWithZScore([
                'beneficiary_id'   => (int)$data['beneficiary_id'],
                'sex'              => $bene['sex'],
                'age_in_months'    => (int)(($d1b=new \DateTime($bene['date_of_birth']))->diff($d2b=new \DateTime($data['assessment_date']))->m + ($d1b->diff($d2b)->y*12)),
                'assessment_date'  => $data['assessment_date'],
                'weight_kg'        => (float)$data['weight_kg'],
                'height_cm'        => isset($data['height_cm']) ? (float)$data['height_cm'] : null,
                'muac_cm'          => isset($data['muac_cm']) ? (float)$data['muac_cm'] : null,
                'period'           => $period,
                'assessment_year'  => $year,
                'assessed_by'      => $data['assessed_by'] ?? $this->apiUser['full_name'],
                'remarks'          => $data['remarks'] ?? null,
                'created_by'       => $this->userId(),
                'validation_status'=> $autoValidated ? 'validated' : 'pending',
                'validated_by'     => $autoValidated ? $this->userId() : null,
                'validated_at'     => $autoValidated ? date('Y-m-d H:i:s') : null,
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->error("This beneficiary already has a $period $year assessment.", 409);
            }
            throw $e;
        }

        // Auto-enroll in DSP if admin/nutritionist (already validated, no queue needed)
        if ($autoValidated) {
            (new ProgramEnrollment())->autoEnrollDSP($id);
        }

        $stmt = $db->prepare('SELECT * FROM assessments WHERE id = ?');
        $stmt->execute([$id]);
        $created = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->success(['assessment' => $created], 'Assessment recorded');
    }

    /**
     * DELETE /api/assessments/{id}
     */
    public function destroy(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','admin']);
        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT a.*, b.barangay FROM assessments a JOIN beneficiaries b ON b.id=a.beneficiary_id WHERE a.id=?');
        $stmt->execute([$id]);
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) $this->error('Assessment not found', 404);
        if ($this->isBhw() && $row['barangay'] !== $this->userBarangay()) $this->error('Access denied', 403);
        $db->prepare('DELETE FROM assessments WHERE id=?')->execute([$id]);
        $this->success([], 'Assessment deleted');
    }

    /**
     * POST /api/assessments/batch
     */
    public function batch(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder','nutritionist','bhw','bns']);
        $body  = $this->body();
        $items = $body['assessments'] ?? [];
        if (empty($items)) $this->error('No assessments provided', 422);
        $model   = new Assessment();
        $db      = Database::getInstance();
        $created = []; $failed = [];
        $role    = strtolower($this->apiUser['role'] ?? '');
        $autoValidated = in_array($role, ['admin', 'nutritionist']);
        foreach ($items as $data) {
            $errors = $this->validate($data);
            if ($errors) { $failed[] = ['errors' => $errors]; continue; }
            try {
                $stmt = $db->prepare('SELECT * FROM beneficiaries WHERE id=? AND deleted_at IS NULL');
                $stmt->execute([$data['beneficiary_id']]);
                $bene = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$bene) { $failed[] = ['errors' => ['beneficiary not found']]; continue; }
                if (($this->isBhw() || $this->isBns()) && $bene['barangay'] !== $this->userBarangay()) { $failed[] = ['errors' => ['access denied']]; continue; }
                $period = (int)date('n', strtotime($data['assessment_date'])) <= 6 ? 'January' : 'July';
                $year   = (int)date('Y', strtotime($data['assessment_date']));
                $newId  = $model->createWithZScore([
                    'beneficiary_id'   => (int)$data['beneficiary_id'],
                    'sex'              => $bene['sex'],
                    'age_in_months'    => (int)(($d1b=new \DateTime($bene['date_of_birth']))->diff($d2b=new \DateTime($data['assessment_date']))->m + ($d1b->diff($d2b)->y*12)),
                    'assessment_date'  => $data['assessment_date'],
                    'weight_kg'        => (float)$data['weight_kg'],
                    'height_cm'        => isset($data['height_cm']) ? (float)$data['height_cm'] : null,
                    'muac_cm'          => isset($data['muac_cm']) ? (float)$data['muac_cm'] : null,
                    'period'           => $period,
                    'assessment_year'  => $year,
                    'assessed_by'      => $data['assessed_by'] ?? $this->apiUser['full_name'],
                    'remarks'          => $data['remarks'] ?? null,
                    'created_by'       => $this->userId(),
                    'validation_status'=> $autoValidated ? 'validated' : 'pending',
                    'validated_by'     => $autoValidated ? $this->userId() : null,
                    'validated_at'     => $autoValidated ? date('Y-m-d H:i:s') : null,
                ]);
                // Auto-enroll in DSP if admin/nutritionist (already validated)
                if ($autoValidated) {
                    (new ProgramEnrollment())->autoEnrollDSP($newId);
                }
                $created[] = $newId;
            } catch (\PDOException $e) {
                if ($e->getCode() === '23000') {
                    $failed[] = ['beneficiary_id' => $data['beneficiary_id'] ?? null, 'errors' => ['Already assessed for this period']];
                } else {
                    error_log('[AssessmentApiController::batch] ' . $e->getMessage());
                    $failed[] = ['beneficiary_id' => $data['beneficiary_id'] ?? null, 'errors' => [$e->getMessage()]];
                }
            } catch (\Throwable $e) {
                error_log('[AssessmentApiController::batch] ' . $e->getMessage());
                $failed[] = ['beneficiary_id' => $data['beneficiary_id'] ?? null, 'errors' => [$e->getMessage()]];
            }
        }
        $this->success(['created' => count($created), 'failed' => $failed], count($created) . ' assessments saved');
    }

    // -------------------------------------------------------
    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['beneficiary_id']))  $errors[] = 'beneficiary_id is required';
        if (empty($data['assessment_date'])) $errors[] = 'assessment_date is required';
        if (!isset($data['weight_kg']) || $data['weight_kg'] <= 0)
            $errors[] = 'weight_kg must be a positive number';
        if (empty($data['period']) || !in_array($data['period'], ['January','July']))
            $errors[] = 'period must be January or July';
        if (empty($data['assessment_year']))
            $errors[] = 'assessment_year is required';
        return $errors;
    }
}
