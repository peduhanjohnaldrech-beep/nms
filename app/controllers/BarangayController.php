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
        $stmt = $db->query(
            "SELECT barangay,
                    COUNT(*) AS total,
                    SUM(CASE WHEN date_of_birth >= DATE_SUB(NOW(), INTERVAL 59 MONTH) THEN 1 ELSE 0 END) AS active,
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
        $stmt = $db->query(
            "SELECT b.barangay, a.nutritional_status, COUNT(*) AS cnt
             FROM assessments a
             JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL
               AND a.assessment_year = YEAR(NOW())
               AND a.id = (
                   SELECT id FROM assessments a2
                   WHERE a2.beneficiary_id = a.beneficiary_id
                   ORDER BY a2.assessment_date DESC LIMIT 1
               )
             GROUP BY b.barangay, a.nutritional_status"
        );
        $statusByBar = [];
        foreach ($stmt->fetchAll() as $r) {
            $statusByBar[$r['barangay']][$r['nutritional_status']] = (int)$r['cnt'];
        }

        // Merge config list with DB data, exclude numeric-only names
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
}
