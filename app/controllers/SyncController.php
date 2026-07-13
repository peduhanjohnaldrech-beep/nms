<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;
use App\Models\Assessment;
use App\Models\Beneficiary;

/**
 * Offline sync endpoints for the Flutter mobile app.
 *
 * GET  /api/sync/pull  — Download all data for offline use
 * POST /api/sync/push  — Upload records created offline
 */
class SyncController extends ApiController
{
    /**
     * GET /api/sync/pull
     * Returns all data needed for offline work.
     * Query: ?since=2024-01-01T00:00:00 (optional, for incremental sync)
     */
    public function pull(): void
    {
        $this->requireApiAuth();

        $since    = $_GET['since'] ?? null;
        $barangay = $this->isBhw() ? $this->userBarangay() : ($_GET['barangay'] ?? null);

        $db = Database::getInstance();

        // --- Beneficiaries ---
        $bWhere  = ['b.deleted_at IS NULL'];
        $bParams = [];
        if ($barangay) {
            $bWhere[]  = 'b.barangay = ?';
            $bParams[] = $barangay;
        }
        if ($since) {
            $bWhere[]  = 'b.updated_at > ?';
            $bParams[] = $since;
        }
        $bClause = implode(' AND ', $bWhere);
        $stmt = $db->prepare("SELECT * FROM beneficiaries b WHERE $bClause ORDER BY b.id");
        $stmt->execute($bParams);
        $beneficiaries = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Collect beneficiary IDs for assessment filtering
        $beneIds = array_column($beneficiaries, 'id');

        // --- Assessments (only for fetched beneficiaries) ---
        $assessments = [];
        if ($beneIds) {
            $placeholders = implode(',', array_fill(0, count($beneIds), '?'));
            $aWhere  = ["a.beneficiary_id IN ($placeholders)"];
            $aParams = $beneIds;
            if ($since) {
                $aWhere[]  = 'a.created_at > ?';
                $aParams[] = $since;
            }
            $aClause = implode(' AND ', $aWhere);
            $stmt = $db->prepare("SELECT * FROM assessments a WHERE $aClause ORDER BY a.id");
            $stmt->execute($aParams);
            $assessments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // --- Programs (static reference data) ---
        $programs = [];
        try {
            $stmt = $db->prepare("SELECT id, name, code, description FROM programs ORDER BY name");
            $stmt->execute();
            $programs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // programs table may not exist yet — return empty list
        }

        // --- Barangay list ---
        $stmt = $db->prepare("SELECT DISTINCT barangay FROM beneficiaries WHERE deleted_at IS NULL ORDER BY barangay");
        $stmt->execute();
        $barangays = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'barangay');

        $this->success([
            'synced_at'     => date('Y-m-d H:i:s'),
            'beneficiaries' => $beneficiaries,
            'assessments'   => $assessments,
            'programs'      => $programs,
            'barangays'     => $barangays,
        ]);
    }

    /**
     * POST /api/sync/push
     * Upload records created offline.
     *
     * Body:
     * {
     *   "beneficiaries": [ { ...fields, "local_id": "uuid" }, ... ],
     *   "assessments":   [ { ...fields, "local_id": "uuid", "local_beneficiary_id": "uuid" }, ... ]
     * }
     *
     * Returns mapping of local_id → server_id for client to update its local DB.
     */
    public function push(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['encoder', 'nutritionist', 'bhw', 'bns']);

        $body        = $this->body();
        $newBeneficiaries = $body['beneficiaries'] ?? [];
        $newAssessments   = $body['assessments'] ?? [];

        $db = Database::getInstance();
        $db->beginTransaction();

        $idMap   = [];   // local_id => server_id (beneficiaries)
        $results = ['created' => [], 'failed' => []];

        // --- Process new beneficiaries ---
        foreach ($newBeneficiaries as $item) {
            $localId = $item['local_id'] ?? null;
            try {
                $model = new Beneficiary();

                // BHW barangay restriction
                $barangay = $item['barangay'] ?? null;
                if ($this->isBhw()) $barangay = $this->userBarangay();

                $serverId = $model->insert([
                    'last_name'                => trim($item['last_name'] ?? ''),
                    'first_name'               => trim($item['first_name'] ?? ''),
                    'middle_name'              => $item['middle_name'] ?? null,
                    'suffix'                   => $item['suffix'] ?? null,
                    'date_of_birth'            => $item['date_of_birth'] ?? null,
                    'sex'                      => $item['sex'] ?? null,
                    'place_of_birth'           => $item['place_of_birth'] ?? null,
                    'barangay'                 => $barangay,
                    'purok_zone'               => $item['purok_zone'] ?? null,
                    'household_no'             => $item['household_no'] ?? null,
                    'mother_name'              => $item['mother_name'] ?? null,
                    'father_name'              => $item['father_name'] ?? null,
                    'guardian_name'            => $item['guardian_name'] ?? null,
                    'contact_number'           => $item['contact_number'] ?? null,
                    'income_classification'    => $item['income_classification'] ?? null,
                    'philhealth_status'        => $item['philhealth_status'] ?? null,
                    'is_4ps_member'            => (int)($item['is_4ps_member'] ?? 0),
                    'is_pwd_household'         => (int)($item['is_pwd_household'] ?? 0),
                    'is_indigenous_people'     => (int)($item['is_indigenous_people'] ?? 0),
                    'source'                   => 'Mobile',
                    'created_by'               => $this->userId(),
                ]);

                if ($localId) $idMap[$localId] = $serverId;
                $results['created'][] = ['type' => 'beneficiary', 'local_id' => $localId, 'server_id' => $serverId];
            } catch (\Throwable $e) {
                error_log('[SyncController::push] beneficiary local_id=' . $localId . ' — ' . $e->getMessage());
                $results['failed'][] = ['type' => 'beneficiary', 'local_id' => $localId, 'error' => $e->getMessage()];
            }
        }

        // --- Process new assessments ---
        foreach ($newAssessments as $item) {
            $localId = $item['local_id'] ?? null;
            try {
                // Resolve beneficiary_id: may be a local_id that was just created
                $beneficiaryId = $item['beneficiary_id'] ?? null;
                $localBeneId   = $item['local_beneficiary_id'] ?? null;
                if ($localBeneId && isset($idMap[$localBeneId])) {
                    $beneficiaryId = $idMap[$localBeneId];
                }

                if (!$beneficiaryId) {
                    throw new \RuntimeException('beneficiary_id could not be resolved');
                }

                // Verify beneficiary exists and get sex + dob for z-score calc
                $stmt = $db->prepare('SELECT barangay, sex, date_of_birth FROM beneficiaries WHERE id = ? AND deleted_at IS NULL');
                $stmt->execute([$beneficiaryId]);
                $bene = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$bene) throw new \RuntimeException('Beneficiary not found');
                if ($this->isBhw() && $bene['barangay'] !== $this->userBarangay())
                    throw new \RuntimeException('Access denied');

                $assessDate  = $item['assessment_date'] ?? date('Y-m-d');
                $ageInMonths = isset($item['age_in_months'])
                    ? (int)$item['age_in_months']
                    : \DateHelper::ageInMonths($bene['date_of_birth'], $assessDate);

                $model    = new Assessment();
                $serverId = $model->createWithZScore([
                    'beneficiary_id'  => (int)$beneficiaryId,
                    'sex'             => $item['sex'] ?? $bene['sex'],
                    'age_in_months'   => $ageInMonths,
                    'assessment_date' => $assessDate,
                    'weight_kg'       => (float)($item['weight_kg'] ?? 0),
                    'height_cm'       => isset($item['height_cm']) ? (float)$item['height_cm'] : null,
                    'muac_cm'         => isset($item['muac_cm']) ? (float)$item['muac_cm'] : null,
                    'period'          => $item['period'] ?? 'January',
                    'assessment_year' => (int)($item['assessment_year'] ?? date('Y')),
                    'assessed_by'     => $item['assessed_by'] ?? $this->apiUser['full_name'],
                    'remarks'         => $item['remarks'] ?? null,
                    'created_by'      => $this->userId(),
                ]);

                $results['created'][] = ['type' => 'assessment', 'local_id' => $localId, 'server_id' => $serverId];
            } catch (\Throwable $e) {
                error_log('[SyncController::push] assessment local_id=' . $localId . ' — ' . $e->getMessage());
                $results['failed'][] = ['type' => 'assessment', 'local_id' => $localId, 'error' => $e->getMessage()];
            }
        }

        $db->commit();

        // Auto-backup: silently creates a daily backup in database/backups/
        try { \BackupScheduler::maybeBackup(); } catch (\Throwable $_) {}

        $this->success([
            'id_map'  => $idMap,
            'results' => $results,
        ], 'Sync complete');
    }
}
