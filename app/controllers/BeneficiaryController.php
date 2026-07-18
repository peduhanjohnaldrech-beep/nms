<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\Beneficiary;
use App\Models\Assessment;
use App\Models\ProgramEnrollment;
use App\Models\VitaminARecord;
use App\Models\MnpRecord;
use App\Models\LnsSqRecord;
use App\Models\DispensingRecord;

class BeneficiaryController extends Controller
{
    private Beneficiary $model;

    public function __construct()
    {
        $this->model = new Beneficiary();
    }

    public function index(): void
    {
        $this->requireAuth();

        $role      = Session::get('user_role');
        $search    = trim($_GET['search'] ?? '');
        $filterBar = $_GET['barangay'] ?? '';
        $source    = $_GET['source'] ?? '';
        $ageStatus = $_GET['age_status'] ?? '';
        $page      = max(1, (int)($_GET['page'] ?? 1));

        if (in_array($role, ['bhw', 'bns'])) {
            $filterBar = Session::get('user_barangay', '');
        }

        $result    = $this->model->search($search, $filterBar, $page, 25, $source, $ageStatus, $role);
        $barangays = $this->model->getAllBarangays();

        $this->view('beneficiaries/index', array_merge($result, [
            'search'    => $search,
            'barangays' => $barangays,
            'filterBar' => $filterBar,
            'source'    => $source,
            'ageStatus' => $ageStatus,
        ]));
    }

    public function followup(): void
    {
        $this->requireAuth();
        $this->requirePermission('beneficiaries');

        $role     = Session::get('user_role');
        $barangay = in_array($role, ['bhw', 'bns']) ? Session::get('user_barangay', '') : '';
        $rows     = (new Assessment())->getWorsenedBeneficiaries($barangay);

        $this->view('beneficiaries/followup', ['rows' => $rows]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->requirePermission('beneficiaries');

        $barangays      = $this->model->getAllBarangays();
        $userRole       = Session::get('user_role');
        $userBarangay   = Session::get('user_barangay', '');
        $lockedBarangay = in_array($userRole, ['bhw', 'bns', 'midwife']) && !empty($userBarangay);
        $locationDefaults = [
            'region'           => APP_REGION,
            'province'         => APP_PROVINCE,
            'city_municipality'=> APP_CITY,
        ];

        if ($this->isPost()) {
            $this->validateCsrf();
            $data   = $this->sanitizeInput($_POST);
            $errors = $this->validateBeneficiary($data);

            if (!empty($errors)) {
                Session::flash('error', implode('<br>', $errors));
                $this->view('beneficiaries/create', ['data' => $data, 'barangays' => $barangays, 'lockedBarangay' => $lockedBarangay, 'locationDefaults' => $locationDefaults]);
                return;
            }

            if ($lockedBarangay) {
                $data['barangay'] = $userBarangay; // BHW/BNS locked to their barangay
            }

            $data['created_by']        = Session::get('user_id');
            $data['source']            = 'Walk-in';
            $data['validation_status'] = in_array(Session::get('user_role'), ['bhw', 'bns', 'encoder'])
                ? 'pending' : 'validated';

            // Handle photo upload
            $data['photo'] = $this->handlePhotoUpload();

            $id = $this->model->insert($data);
            \ActivityLog::log('beneficiary_create', "Added beneficiary ID $id: {$data['last_name']}, {$data['first_name']}");
            Session::flash('success', 'Beneficiary added successfully.');
            $this->redirect("/beneficiaries/{$id}");
        }

        $this->view('beneficiaries/create', ['data' => [], 'barangays' => $barangays, 'lockedBarangay' => $lockedBarangay, 'locationDefaults' => $locationDefaults]);
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $id  = (int)$id;
        $db  = \Core\Database::getInstance();
        $row = $db->prepare(
            "SELECT b.*, u.full_name AS validated_by_name
             FROM beneficiaries b
             LEFT JOIN users u ON u.id = b.validated_by
             WHERE b.id = ?"
        );
        $row->execute([$id]);
        $beneficiary = $row->fetch(\PDO::FETCH_ASSOC);

        if (!$beneficiary || $beneficiary['deleted_at']) {
            $this->redirect('/beneficiaries');
        }

        if (in_array(Session::get('user_role'), ['bhw', 'bns']) && $beneficiary['barangay'] !== Session::get('user_barangay')) {
            Session::flash('error', 'Access denied.');
            $this->redirect('/beneficiaries');
        }

        $assessments = (new Assessment())->findByBeneficiary($id);
        $enrollments = (new ProgramEnrollment())->findByBeneficiary($id);
        $vitaminRecs = (new VitaminARecord())->getByBeneficiary($id);
        $mnpRecs     = (new MnpRecord())->getByBeneficiary($id);
        $lnsRecs       = (new LnsSqRecord())->getByBeneficiary($id);
        $dispensingRecs = (new DispensingRecord())->getByBeneficiary($id);
        $trend         = (new Assessment())->getStatusTrend($id);

        $this->view('beneficiaries/show', [
            'beneficiary'    => $beneficiary,
            'assessments'    => $assessments,
            'enrollments'    => $enrollments,
            'vitaminRecs'    => $vitaminRecs,
            'mnpRecs'        => $mnpRecs,
            'lnsRecs'        => $lnsRecs,
            'dispensingRecs' => $dispensingRecs,
            'trend'          => $trend,
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $this->requirePermission('beneficiaries');

        $id          = (int)$id;
        $beneficiary = $this->model->findById($id);
        $barangays   = $this->model->getAllBarangays();
        $locationDefaults = ['region' => APP_REGION, 'province' => APP_PROVINCE, 'city_municipality' => APP_CITY];

        if (!$beneficiary || $beneficiary['deleted_at']) {
            $this->redirect('/beneficiaries');
        }

        if ($this->isPost()) {
            $this->validateCsrf();
            $data   = $this->sanitizeInput($_POST);
            $errors = $this->validateBeneficiary($data);

            if (!empty($errors)) {
                Session::flash('error', implode('<br>', $errors));
                $this->view('beneficiaries/edit', ['data' => $data, 'beneficiary' => $beneficiary, 'barangays' => $barangays, 'locationDefaults' => $locationDefaults]);
                return;
            }

            // Handle photo upload (only replace if a new one is uploaded)
            $newPhoto = $this->handlePhotoUpload();
            if ($newPhoto !== null) {
                // Delete old photo if exists
                if (!empty($beneficiary['photo'])) {
                    $oldPath = UPLOAD_PATH . '/photos/' . $beneficiary['photo'];
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $data['photo'] = $newPhoto;
            } else {
                $data['photo'] = $beneficiary['photo'];
            }

            // BHW/encoder editing a rejected record → reset to pending
            if (in_array(Session::get('user_role'), ['bhw', 'encoder'])
                && ($beneficiary['validation_status'] ?? '') === 'rejected') {
                $data['validation_status'] = 'pending';
                $data['rejection_note']    = null;
                $data['validated_by']      = null;
                $data['validated_at']      = null;
            }

            $this->model->update($id, $data);
            \ActivityLog::log('beneficiary_update', "Updated beneficiary ID $id: {$data['last_name']}, {$data['first_name']}");
            Session::flash('success', 'Beneficiary updated successfully.');
            $this->redirect("/beneficiaries/{$id}");
        }

        $this->view('beneficiaries/edit', [
            'beneficiary'      => $beneficiary,
            'data'             => $beneficiary,
            'barangays'        => $barangays,
            'locationDefaults' => $locationDefaults,
        ]);
    }

    public function validation(): void
    {
        $this->requireAuth();
        if (!in_array(Session::get('user_role'), ['midwife','admin','nutritionist'])) {
            Session::flash('error', 'Access denied.');
            $this->redirect('/dashboard');
        }

        $db            = \Core\Database::getInstance();
        $sessionBrgy   = Session::get('user_barangay');
        $filterBrgy    = $sessionBrgy ?: trim($_GET['barangay'] ?? '');
        $where         = ["b.validation_status = 'pending'", 'b.deleted_at IS NULL'];
        $params        = [];

        if ($filterBrgy) {
            $where[]  = 'b.barangay = ?';
            $params[] = $filterBrgy;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $stmt = $db->prepare(
            "SELECT b.*, u.full_name AS created_by_name
             FROM beneficiaries b
             LEFT JOIN users u ON u.id = b.created_by
             $whereClause
             ORDER BY b.created_at DESC"
        );
        $stmt->execute($params);
        $rows      = $stmt->fetchAll();
        $barangays = $sessionBrgy ? [] : $this->model->getAllBarangays();

        $this->view('beneficiaries/validation', [
            'rows'         => $rows,
            'barangays'    => $barangays,
            'filterBrgy'   => $filterBrgy,
            'sessionBrgy'  => $sessionBrgy,
        ]);
    }

    public function validate(string $id): void
    {
        $this->requireAuth();
        if (!$this->isPost()) { $this->redirect('/beneficiaries'); }
        $this->validateCsrf();

        if (!in_array(Session::get('user_role'), ['midwife','admin','nutritionist'])) {
            Session::flash('error', 'Access denied.');
            $this->redirect('/beneficiaries');
        }

        $db   = \Core\Database::getInstance();
        $brgy = Session::get('user_barangay');

        if ($brgy) {
            $chk = $db->prepare("SELECT barangay FROM beneficiaries WHERE id = ? AND deleted_at IS NULL");
            $chk->execute([(int)$id]);
            $rec = $chk->fetch();
            if (!$rec || $rec['barangay'] !== $brgy) {
                Session::flash('error', 'Access denied: beneficiary is not in your barangay.');
                $this->redirect('/beneficiaries/validation');
            }
        }

        $db->prepare(
            "UPDATE beneficiaries SET validation_status='validated', validated_by=?, validated_at=NOW(), rejection_note=NULL WHERE id=?"
        )->execute([Session::get('user_id'), (int)$id]);

        \ActivityLog::log('beneficiary_validate', "Validated beneficiary ID $id");
        Session::flash('success', 'Beneficiary registration approved.');
        $this->redirect("/beneficiaries/{$id}");
    }

    public function reject(string $id): void
    {
        $this->requireAuth();
        if (!$this->isPost()) { $this->redirect('/beneficiaries'); }
        $this->validateCsrf();

        if (!in_array(Session::get('user_role'), ['midwife','admin','nutritionist'])) {
            Session::flash('error', 'Access denied.');
            $this->redirect('/beneficiaries');
        }

        $note = trim($_POST['rejection_note'] ?? '');
        $db   = \Core\Database::getInstance();
        $brgy = Session::get('user_barangay');

        if ($brgy) {
            $chk = $db->prepare("SELECT barangay FROM beneficiaries WHERE id = ? AND deleted_at IS NULL");
            $chk->execute([(int)$id]);
            $rec = $chk->fetch();
            if (!$rec || $rec['barangay'] !== $brgy) {
                Session::flash('error', 'Access denied: beneficiary is not in your barangay.');
                $this->redirect('/beneficiaries/validation');
            }
        }

        $db->prepare(
            "UPDATE beneficiaries SET validation_status='rejected', validated_by=?, validated_at=NOW(), rejection_note=? WHERE id=?"
        )->execute([Session::get('user_id'), $note, (int)$id]);

        \ActivityLog::log('beneficiary_reject', "Rejected beneficiary ID $id. Note: $note");
        Session::flash('warning', 'Beneficiary registration rejected.');
        $this->redirect("/beneficiaries/{$id}");
    }

    public function checkDuplicate(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json');

        $lastName  = trim($_GET['last_name']    ?? '');
        $firstName = trim($_GET['first_name']   ?? '');
        $dob       = trim($_GET['date_of_birth'] ?? '');
        $excludeId = (int)($_GET['exclude_id']   ?? 0);

        if (empty($lastName) || empty($firstName) || empty($dob)) {
            echo json_encode(['duplicate' => false]);
            return;
        }

        $matches = $this->model->findDuplicatesByNameDob($lastName, $firstName, $dob);
        if ($excludeId) {
            $matches = array_filter($matches, fn($m) => (int)$m['id'] !== $excludeId);
        }

        echo json_encode(['duplicate' => !empty($matches)]);
        exit;
    }

    public function trash(): void
    {
        $this->requireAuth();
        $this->requirePermission('beneficiaries');

        $db   = \Core\Database::getInstance();
        $stmt = $db->prepare(
            "SELECT b.*, (SELECT MAX(assessment_date) FROM assessments WHERE beneficiary_id = b.id) AS last_assessed
             FROM beneficiaries b WHERE b.deleted_at IS NOT NULL ORDER BY b.deleted_at DESC LIMIT 200"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $this->view('beneficiaries/trash', ['rows' => $rows]);
    }

    public function restore(string $id): void
    {
        $this->requireAuth();
        $this->requirePermission('beneficiaries');
        if (!$this->isPost()) { $this->redirect('/beneficiaries/trash'); }
        $this->validateCsrf();

        $id = (int)$id;
        $db = \Core\Database::getInstance();
        $stmt = $db->prepare("UPDATE beneficiaries SET deleted_at = NULL WHERE id = ?");
        $stmt->execute([$id]);

        $b = $this->model->findById($id);
        \ActivityLog::log('beneficiary_restore', "Restored beneficiary ID $id" . ($b ? ": {$b['last_name']}, {$b['first_name']}" : ''));
        Session::flash('success', 'Beneficiary restored.');
        $this->redirect('/beneficiaries/trash');
    }

    public function submitToAdmin(string $id): void
    {
        $this->requireAuth();
        $this->requirePermission('beneficiaries');
        if (!$this->isPost()) { $this->redirect('/beneficiaries'); }
        $this->validateCsrf();

        $id  = (int)$id;
        $b   = $this->model->findById($id);

        if (!$b || $b['deleted_at'] || ($b['validation_status'] ?? '') !== 'validated') {
            Session::flash('error', 'Only validated beneficiaries can be submitted.');
            $this->redirect('/beneficiaries');
        }

        if (!empty($b['submitted_at'])) {
            Session::flash('info', 'Already submitted to admin.');
            $this->redirect("/beneficiaries/{$id}");
        }

        $this->model->submitToAdmin($id, Session::get('user_id'));
        \ActivityLog::log('beneficiary_submit', "Submitted beneficiary ID $id to admin: {$b['last_name']}, {$b['first_name']}");
        Session::flash('success', 'Beneficiary submitted to admin.');
        $this->redirect("/beneficiaries/{$id}");
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->requirePermission('beneficiaries');
        if (!$this->isPost()) { $this->redirect('/beneficiaries'); }
        $this->validateCsrf();

        $b = $this->model->findById((int)$id);
        $this->model->softDelete((int)$id);
        \ActivityLog::log('beneficiary_delete', "Deleted beneficiary ID $id" . ($b ? ": {$b['last_name']}, {$b['first_name']}" : ''));
        Session::flash('success', 'Beneficiary removed.');
        $this->redirect('/beneficiaries');
    }

    private function handlePhotoUpload(): ?string
    {
        if (empty($_FILES['photo']['name'])) return null;

        $file = $_FILES['photo'];
        if ($file['error'] !== UPLOAD_ERR_OK) return null;

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo        = finfo_open(FILEINFO_MIME_TYPE);
        $mime         = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes)) return null;
        if ($file['size'] > 2 * 1024 * 1024) return null; // 2MB max

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'photo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest     = UPLOAD_PATH . '/photos/' . $filename;

        if (!is_dir(UPLOAD_PATH . '/photos')) {
            mkdir(UPLOAD_PATH . '/photos', 0755, true);
        }

        return move_uploaded_file($file['tmp_name'], $dest) ? $filename : null;
    }

    private function sanitizeInput(array $post): array
    {
        $fields = [
            'last_name','first_name','middle_name','suffix','date_of_birth','sex',
            'place_of_birth','region','province','city_municipality',
            'barangay','purok_zone','household_no','incode',
            'mother_name','father_name','guardian_name','guardian_relationship',
            'contact_number','income_classification','income_source',
            'philhealth_status','nhts_pr_status','ip_group',
        ];
        $data = [];
        foreach ($fields as $f) {
            $data[$f] = trim(strip_tags($post[$f] ?? '')) ?: null;
        }
        $data['household_monthly_income'] = is_numeric($post['household_monthly_income'] ?? '') ? (float)$post['household_monthly_income'] : null;
        $data['is_4ps_member']       = isset($post['is_4ps_member']) ? 1 : 0;
        $data['is_pwd_household']    = isset($post['is_pwd_household']) ? 1 : 0;
        $data['is_indigenous_people']= isset($post['is_indigenous_people']) ? 1 : 0;
        return $data;
    }

    private function validateBeneficiary(array $data): array
    {
        $errors = [];
        if (empty($data['last_name']))     $errors[] = 'Last name is required.';
        if (empty($data['first_name']))    $errors[] = 'First name is required.';
        if (empty($data['date_of_birth'])) {
            $errors[] = 'Date of birth is required.';
        } else {
            $ageMonths = \DateHelper::ageInMonths($data['date_of_birth']);
            if ($ageMonths < 0) {
                $errors[] = 'Date of birth cannot be in the future.';
            } elseif ($ageMonths > 59) {
                $errors[] = 'Beneficiary must be 0–59 months old. This child is ' . $ageMonths . ' months old.';
            }
        }
        if (empty($data['sex']))       $errors[] = 'Sex is required.';
        if (empty($data['barangay'])) {
            $errors[] = 'Barangay is required.';
        } else {
            $validBarangays = array_column($this->model->getAllBarangays(), 'barangay');
            if (!in_array($data['barangay'], $validBarangays)) {
                $errors[] = 'Selected barangay is not in the list.';
            }
        }
        return $errors;
    }
}
