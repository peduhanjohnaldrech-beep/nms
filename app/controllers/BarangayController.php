<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use App\Models\Beneficiary;

class BarangayController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->requirePermission('beneficiaries');

        $db   = Database::getInstance();
        $role = Session::get('user_role');

        // BHW/BNS only see their own barangay
        if (in_array($role, ['bhw', 'bns'])) {
            $this->redirect('/beneficiaries?barangay=' . urlencode(Session::get('user_barangay', '')));
        }

        // Beneficiary counts per barangay
        // total  = validated + BNS-submitted (matches what beneficiary list shows without age filter)
        // active = validated + BNS-submitted + 0-59 months (matches beneficiary list default view)
        // pending = awaiting validation (informational)
        $bnsNoAlias = Beneficiary::BNS_SUBMITTED_FILTER_NO_ALIAS;
        $stmt = $db->query(
            "SELECT barangay,
                    SUM(CASE WHEN validation_status = 'validated' AND $bnsNoAlias THEN 1 ELSE 0 END) AS total,
                    SUM(CASE WHEN validation_status = 'validated' AND $bnsNoAlias AND TIMESTAMPDIFF(MONTH, date_of_birth, CURDATE()) <= 59 THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN validation_status = 'pending' THEN 1 ELSE 0 END) AS pending
             FROM beneficiaries
             WHERE deleted_at IS NULL AND barangay IS NOT NULL AND barangay != ''
             GROUP BY barangay
             ORDER BY barangay"
        );
        $counts = [];
        foreach ($stmt->fetchAll() as $r) {
            $counts[$r['barangay']] = $r;
        }

        // Latest nutritional status per beneficiary, grouped by barangay
        $bnsFilter = Beneficiary::BNS_SUBMITTED_FILTER;
        $stmt = $db->query(
            "SELECT b.barangay, a.nutritional_status, COUNT(*) AS cnt
             FROM assessments a
             JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL
               AND b.validation_status = 'validated'
               AND $bnsFilter
               AND a.validation_status = 'validated'
               AND a.assessment_year = YEAR(NOW())
               AND a.id = (
                   SELECT id FROM assessments a2
                   WHERE a2.beneficiary_id = a.beneficiary_id
                     AND a2.validation_status = 'validated'
                   ORDER BY a2.assessment_date DESC LIMIT 1
               )
             GROUP BY b.barangay, a.nutritional_status"
        );
        $statusByBar = [];
        foreach ($stmt->fetchAll() as $r) {
            $statusByBar[$r['barangay']][$r['nutritional_status']] = (int)$r['cnt'];
        }

        // Fetch managed barangays with their IDs
        try {
            $managedRows = $db->query("SELECT id, name FROM barangays ORDER BY name")->fetchAll();
        } catch (\Throwable $e) {
            $managedRows = [];
        }
        $managedIds = [];
        foreach ($managedRows as $r) {
            $managedIds[$r['name']] = $r['id'];
        }

        // Merge managed list with DB data, exclude numeric-only names
        $configBars = array_column((new Beneficiary())->getAllBarangays(), 'barangay');
        $allBars    = array_filter(
            array_unique(array_merge($configBars, array_keys($counts))),
            fn($b) => !is_numeric(trim($b))
        );
        sort($allBars);

        $barangays = [];
        foreach ($allBars as $bar) {
            $c = $counts[$bar] ?? ['total' => 0, 'active' => 0, 'pending' => 0];
            $s = $statusByBar[$bar] ?? [];
            $barangays[] = [
                'id'      => $managedIds[$bar] ?? null,
                'name'    => $bar,
                'total'   => (int)$c['total'],
                'active'  => (int)$c['active'],
                'pending' => (int)$c['pending'],
                'suw'     => $s['SUW']    ?? 0,
                'uw'      => $s['UW']     ?? 0,
                'normal'  => $s['Normal'] ?? 0,
                'ow'      => $s['OW']     ?? 0,
                'ob'      => $s['OB']     ?? 0,
            ];
        }

        $this->view('barangays/index', ['barangays' => $barangays]);
    }

    public function store(): void
    {
        $this->requireAdmin();

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'Barangay name cannot be empty.');
            $this->redirect('/barangays');
        }

        $db = Database::getInstance();

        // Check duplicate
        $exists = $db->prepare("SELECT id FROM barangays WHERE LOWER(name) = LOWER(?)");
        $exists->execute([$name]);
        if ($exists->fetch()) {
            Session::flash('error', "Barangay \"" . htmlspecialchars($name) . "\" already exists.");
            $this->redirect('/barangays');
        }

        $db->prepare("INSERT INTO barangays (name) VALUES (?)")->execute([$name]);
        Session::flash('success', "Barangay \"" . htmlspecialchars($name) . "\" added successfully.");
        $this->redirect('/barangays');
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();

        $db  = Database::getInstance();
        $row = $db->prepare("SELECT id, name FROM barangays WHERE id = ?");
        $row->execute([$id]);
        $bar = $row->fetch();

        if (!$bar) {
            Session::flash('error', 'Barangay not found.');
            $this->redirect('/barangays');
        }

        // Prevent deletion if beneficiaries are assigned to this barangay
        $inUse = $db->prepare(
            "SELECT COUNT(*) FROM beneficiaries WHERE barangay = ? AND deleted_at IS NULL"
        );
        $inUse->execute([$bar['name']]);
        if ((int)$inUse->fetchColumn() > 0) {
            Session::flash('error', "Cannot delete \"" . htmlspecialchars($bar['name']) . "\" — it still has beneficiaries assigned.");
            $this->redirect('/barangays');
        }

        $db->prepare("DELETE FROM barangays WHERE id = ?")->execute([$id]);
        Session::flash('success', "Barangay \"" . htmlspecialchars($bar['name']) . "\" deleted.");
        $this->redirect('/barangays');
    }
}
