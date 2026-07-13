<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;

class ProgramsAdminApiController extends ApiController
{
    /**
     * GET /api/programs/list
     * All active programs — used by mobile sidebar
     */
    public function list(): void
    {
        $this->requireApiAuth();
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, code, name, description, icon, color, type, is_active, sort_order
             FROM programs
             WHERE is_active = 1
             ORDER BY sort_order, id"
        );
        $stmt->execute();
        $this->success(['programs' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    /**
     * GET /api/programs-admin
     * All programs (active + inactive) — admin management
     */
    public function index(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['admin']);
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, code, name, description, icon, color, type, is_active, sort_order
             FROM programs
             ORDER BY sort_order, id"
        );
        $stmt->execute();
        $this->success(['programs' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    /**
     * POST /api/programs-admin
     * Create a new program
     */
    public function store(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['admin']);

        $body = $this->body();
        $code = strtoupper(trim($body['code'] ?? ''));
        $name = trim($body['name'] ?? '');

        if (!$code || !$name) {
            $this->error('Code and name are required', 422);
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO programs (code, name, description, icon, color, type, is_active, sort_order)
             VALUES (?,?,?,?,?,?,1,?)"
        );
        $stmt->execute([
            $code,
            $name,
            trim($body['description'] ?? ''),
            trim($body['icon']        ?? 'bi-clipboard-check'),
            trim($body['color']       ?? 'primary'),
            $body['type']             ?? 'generic',
            (int)($body['sort_order'] ?? 0),
        ]);
        $this->json(['success' => true, 'message' => "Program '$name' created", 'data' => ['id' => $db->lastInsertId()]], 201);
    }

    /**
     * PUT /api/programs-admin/{id}
     * Update a program
     */
    public function update(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['admin']);

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM programs WHERE id = ?");
        $stmt->execute([$id]);
        $prog = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$prog) $this->error('Program not found', 404);

        $body   = $this->body();
        $fields = [];
        $params = [];

        foreach (['name','description','icon','color','type','sort_order'] as $f) {
            if (array_key_exists($f, $body)) {
                $fields[] = "$f = ?";
                $params[] = $f === 'sort_order' ? (int)$body[$f] : trim($body[$f]);
            }
        }
        if (array_key_exists('is_active', $body)) {
            $fields[] = 'is_active = ?';
            $params[] = $body['is_active'] ? 1 : 0;
        }

        if ($fields) {
            $params[] = $id;
            $db->prepare("UPDATE programs SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
        }

        $this->success([], 'Program updated');
    }

    /**
     * POST /api/programs-admin/{id}/toggle
     * Toggle active/inactive
     */
    public function toggle(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['admin']);

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT id, is_active, name FROM programs WHERE id = ?");
        $stmt->execute([$id]);
        $prog = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$prog) $this->error('Program not found', 404);

        $newState = $prog['is_active'] ? 0 : 1;
        $db->prepare("UPDATE programs SET is_active = ? WHERE id = ?")->execute([$newState, $id]);
        $this->success(['is_active' => $newState], 'Program ' . ($newState ? 'activated' : 'deactivated'));
    }
}
