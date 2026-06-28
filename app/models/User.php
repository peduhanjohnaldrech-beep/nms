<?php

namespace App\Models;

use Core\Model;

class User extends Model
{
    protected string $table = 'users';

    public function authenticate(string $username, string $password): array|false
    {
        $user = $this->findByUsername($username);
        if (!$user || !$user['is_active']) return false;
        if (!password_verify($password, $user['password_hash'])) return false;
        return $user;
    }

    public function findByUsername(string $username): array|false
    {
        return $this->fetch("SELECT * FROM users WHERE username = ? LIMIT 1", [$username]);
    }

    public function getAllUsers(): array
    {
        return $this->fetchAll(
            "SELECT id, username, full_name, role, barangay, is_active, created_at FROM users ORDER BY full_name"
        );
    }

    public function createUser(array $data): int
    {
        return $this->insert([
            'username'      => $data['username'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'full_name'     => $data['full_name'],
            'role'          => $data['role'],
            'barangay'      => $data['barangay'] ?? null,
            'permissions'   => json_encode($data['permissions'] ?? []),
            'is_active'     => 1,
        ]);
    }

    public function updateUser(int $id, array $data): bool
    {
        $fields = [
            'full_name'   => $data['full_name'],
            'role'        => $data['role'],
            'barangay'    => $data['barangay'] ?? null,
            'permissions' => json_encode($data['permissions'] ?? []),
        ];
        if (!empty($data['password'])) {
            $fields['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        return $this->update($id, $fields);
    }

    public function deactivate(int $id): bool
    {
        return $this->update($id, ['is_active' => 0]);
    }

    public function activate(int $id): bool
    {
        return $this->update($id, ['is_active' => 1]);
    }

    public function deleteUser(int $id): bool
    {
        $db   = \Core\Database::getInstance();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
