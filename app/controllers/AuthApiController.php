<?php
namespace App\Controllers;

use Core\ApiController;
use Core\Database;
use App\Models\User;

class AuthApiController extends ApiController
{
    /**
     * POST /api/auth/login
     * Body: { username, password, device_name? }
     */
    public function login(): void
    {
        $body = $this->body();
        $username   = trim($body['username'] ?? '');
        $password   = $body['password'] ?? '';
        $deviceName = trim($body['device_name'] ?? 'Mobile');

        if (!$username || !$password) {
            $this->error('Username and password are required');
        }

        $userModel = new User();
        $user = $userModel->authenticate($username, $password);

        if (!$user) {
            $this->error('Invalid credentials or account is inactive', 401);
        }

        // Generate token (32 bytes = 64 hex chars)
        $token = bin2hex(random_bytes(32));
        // Expires in 30 days
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        $db = Database::getInstance();
        $stmt = $db->prepare('
            INSERT INTO api_tokens (user_id, token, device_name, expires_at)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$user['id'], $token, $deviceName, $expiresAt]);

        $this->success([
            'token'      => $token,
            'expires_at' => $expiresAt,
            'user'       => [
                'id'          => $user['id'],
                'username'    => $user['username'],
                'full_name'   => $user['full_name'],
                'role'        => $user['role'],
                'barangay'    => $user['barangay'],
                'permissions' => json_decode($user['permissions'] ?? '[]', true) ?: [],
            ],
        ], 'Login successful');
    }

    /**
     * POST /api/auth/logout
     * Header: Authorization: Bearer {token}
     */
    public function logout(): void
    {
        $token = $this->getBearerToken();
        if ($token) {
            $db = Database::getInstance();
            $db->prepare('DELETE FROM api_tokens WHERE token = ?')->execute([$token]);
        }
        $this->success(null, 'Logged out');
    }

    /**
     * GET /api/auth/me
     * Returns current user info
     */
    public function me(): void
    {
        $this->requireApiAuth();
        $this->success([
            'id'          => $this->apiUser['user_id'],
            'username'    => $this->apiUser['username'],
            'full_name'   => $this->apiUser['full_name'],
            'role'        => $this->apiUser['role'],
            'barangay'    => $this->apiUser['barangay'],
            'permissions' => json_decode($this->apiUser['permissions'] ?? '[]', true) ?: [],
        ]);
    }
}
