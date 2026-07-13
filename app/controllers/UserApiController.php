<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;

class UserApiController extends ApiController
{
    public function index(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['admin']);
        $db   = Database::getInstance();
        $stmt = $db->query("SELECT id, username, full_name, role, barangay, permissions, is_active, created_at FROM users ORDER BY role, full_name");
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC); foreach ($users as &$u) { $u['permissions'] = $u['permissions'] ? json_decode($u['permissions'], true) : []; } $this->success(['users' => $users]);
    }

    public function store(): void
    {
        $this->requireApiAuth();
        $this->requireRole(['admin']);
        $data = $this->body();
        if (empty($data['username'])) $this->error('username required', 422);
        if (empty($data['password'])) $this->error('password required', 422);
        if (empty($data['role']))     $this->error('role required', 422);

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM users WHERE username=?');
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) $this->error('Username already exists', 409);

        $db->prepare("INSERT INTO users (username, password_hash, full_name, role, barangay, permissions, is_active) VALUES (?,?,?,?,?,?,1)")
           ->execute([$data['username'], password_hash($data['password'], PASSWORD_DEFAULT), $data['full_name'] ?? '', $data['role'], $data['barangay']??null, json_encode($data['permissions'] ?? [])]);
        $this->success([], 'User created');
    }

    public function update(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['admin']);
        $data = $this->body();
        $db   = Database::getInstance();

        $stmt = $db->prepare('SELECT id FROM users WHERE id=?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) $this->error('User not found', 404);

        $fields = []; $params = [];
        if (isset($data['full_name'])) { $fields[] = 'full_name=?'; $params[] = $data['full_name']; }
        if (isset($data['role']))      { $fields[] = 'role=?'; $params[] = $data['role']; }
        if (isset($data['barangay']))  { $fields[] = 'barangay=?'; $params[] = $data['barangay']; }
        if (isset($data['permissions'])) { $fields[] = 'permissions=?'; $params[] = json_encode($data['permissions']); }
        if (isset($data['is_active'])) { $fields[] = 'is_active=?'; $params[] = (int)$data['is_active']; }
        if (!empty($data['password'])) { $fields[] = 'password_hash=?'; $params[] = password_hash($data['password'], PASSWORD_DEFAULT); }

        if ($fields) {
            $params[] = $id;
            $db->prepare('UPDATE users SET ' . implode(',', $fields) . ' WHERE id=?')->execute($params);
        }
        $this->success([], 'User updated');
    }

    public function destroy(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['admin']);
        $db = Database::getInstance();
        $db->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
        $this->success([], 'User deleted');
    }

    public function activate(string $id): void
    {
        $this->requireApiAuth();
        $this->requireRole(['admin']);
        $db   = Database::getInstance();
        $data = $this->body();
        $db->prepare('UPDATE users SET is_active=? WHERE id=?')->execute([(int)($data['is_active'] ?? 1), $id]);
        $this->success([], 'User status updated');
    }
}
