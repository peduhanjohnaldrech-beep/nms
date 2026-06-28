<?php

namespace App\Controllers;

use Core\Controller;
use Core\Database;

class ActivityController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->requirePermission('activity_log');

        $db     = Database::getInstance();
        $action = trim($_GET['action'] ?? '');
        $user   = trim($_GET['user']   ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 50;
        $offset = ($page - 1) * $limit;

        $where  = ['1=1'];
        $params = [];
        if ($action !== '') { $where[] = 'action = ?';              $params[] = $action; }
        if ($user !== '')   { $where[] = 'user_name LIKE ?';        $params[] = '%' . $user . '%'; }
        $cond = implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM activity_logs WHERE $cond");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT * FROM activity_logs WHERE $cond ORDER BY created_at DESC LIMIT $limit OFFSET $offset"
        );
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $actions = $db->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")->fetchAll(\PDO::FETCH_COLUMN);

        $this->view('activity/index', [
            'logs'       => $logs,
            'total'      => $total,
            'page'       => $page,
            'totalPages' => (int) ceil($total / $limit),
            'actions'    => $actions,
            'filterAction' => $action,
            'filterUser'   => $user,
        ]);
    }
}
