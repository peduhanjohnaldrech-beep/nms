<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;

class DispensingApiController extends ApiController
{
    public function index(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $db   = Database::getInstance();

        $year    = (int)($_GET['year']    ?? date('Y'));
        $program = $_GET['program']       ?? '';
        $role    = strtolower($user['role'] ?? '');
        $brgy    = in_array($role, ['bhw','encoder']) ? ($user['barangay'] ?? null) : ($_GET['barangay'] ?? null);

        $where  = ['b.deleted_at IS NULL', 'd.year = ?'];
        $params = [$year];
        if ($program) { $where[] = 'd.program_id = ?'; $params[] = $program; }
        if ($brgy)    { $where[] = 'b.barangay = ?';   $params[] = $brgy; }

        try {
            $stmt = $db->prepare(
                "SELECT d.*, b.last_name, b.first_name, b.barangay, p.name as program_name
                 FROM dispensing_records d
                 JOIN beneficiaries b ON b.id = d.beneficiary_id
                 LEFT JOIN programs p ON p.id = d.program_id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY d.date_dispensed DESC
                 LIMIT 200"
            );
            $stmt->execute($params);
            $records = $stmt->fetchAll();
            $this->success(['records' => $records, 'year' => $year]);
        } catch (\Throwable $e) {
            $this->success(['records' => [], 'year' => $year, 'note' => 'dispensing_records table not yet created']);
        }
    }

    public function store(): void
    {
        $this->requireAuth(); $user = $this->apiUser;
        $body = $this->body();
        $db   = Database::getInstance();

        $required = ['beneficiary_id', 'program_id', 'date_dispensed', 'quantity'];
        foreach ($required as $f) {
            if (empty($body[$f])) {
                $this->error("$f is required", 422);
                return;
            }
        }

        try {
            $stmt = $db->prepare(
                "INSERT INTO dispensing_records (beneficiary_id, program_id, date_dispensed, quantity, notes, year, recorded_by)
                 VALUES (?,?,?,?,?,?,?)"
            );
            $stmt->execute([
                $body['beneficiary_id'],
                $body['program_id'],
                $body['date_dispensed'],
                $body['quantity'],
                $body['notes']    ?? null,
                $body['year']     ?? date('Y'),
                $user['username'] ?? 'api',
            ]);
            $id = $db->lastInsertId();
            $this->json(['success' => true, 'message' => 'Dispensing record saved', 'data' => ['id' => $id]], 201);
        } catch (\Throwable $e) {
            $this->error('Failed to save: ' . $e->getMessage(), 500);
        }
    }
}
