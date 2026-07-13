<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;

class ActivityApiController extends ApiController
{
    public function index(): void
    {
        $this->requireAuth();
        $user = $this->apiUser;
        $db   = Database::getInstance();

        $role    = strtolower($user['role'] ?? '');
        $limit   = min((int)($_GET['limit'] ?? 50), 200);
        $page    = max((int)($_GET['page']  ?? 1), 1);
        $offset  = ($page - 1) * $limit;

        try {
            $where  = [];
            $params = [];

            // Non-admins only see their own activity
            if (!in_array($role, ['admin', 'nutritionist'])) {
                $where[]  = 'username = ?';
                $params[] = $user['username'];
            }

            $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $stmt = $db->prepare(
                "SELECT id, username, action, description, created_at
                 FROM activity_logs
                 $whereStr
                 ORDER BY created_at DESC
                 LIMIT $limit OFFSET $offset"
            );
            $stmt->execute($params);
            $logs = $stmt->fetchAll();

            $this->success(['logs' => $logs, 'page' => $page]);
        } catch (\Throwable $e) {
            $this->success(['logs' => [], 'page' => 1]);
        }
    }
}
