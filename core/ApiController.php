<?php
namespace Core;

class ApiController
{
    protected array $apiUser = [];

    protected function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        echo json_encode($data);
        exit;
    }

    protected function success(mixed $data = null, string $message = 'OK'): never
    {
        $this->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    protected function error(string $message, int $code = 400): never
    {
        $this->json(['success' => false, 'message' => $message], $code);
    }

    protected function requireAuth(): void
    {
        $this->requireApiAuth();
    }

    protected function requireApiAuth(): void
    {
        $token = $this->getBearerToken();
        if (!$token) {
            $this->error('Authorization token required', 401);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('
            SELECT t.user_id, u.username, u.full_name, u.role, u.barangay, u.permissions, u.is_active
            FROM api_tokens t
            JOIN users u ON u.id = t.user_id
            WHERE t.token = ?
              AND (t.expires_at IS NULL OR t.expires_at > NOW())
        ');
        $stmt->execute([$token]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            $this->error('Invalid or expired token', 401);
        }
        if (!$row['is_active']) {
            $this->error('Account is deactivated', 401);
        }

        $this->apiUser = $row;
    }

    protected function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($header, 'Bearer ')) {
            return trim(substr($header, 7));
        }
        return null;
    }

    protected function body(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    protected function requireRole(array $roles): void
    {
        $role = strtolower($this->apiUser['role'] ?? '');
        if ($role === 'admin') return;
        if (!in_array($role, array_map('strtolower', $roles))) {
            $this->error('Access denied', 403);
        }
    }

    protected function isAdmin(): bool
    {
        return strtolower($this->apiUser['role'] ?? '') === 'admin';
    }

    protected function isBhw(): bool
    {
        return strtolower($this->apiUser['role'] ?? '') === 'bhw';
    }

    protected function isMidwife(): bool
    {
        return strtolower($this->apiUser['role'] ?? '') === 'midwife';
    }

    protected function isBns(): bool
    {
        return strtolower($this->apiUser['role'] ?? '') === 'bns';
    }

    // Field workers whose submissions go through validation (pending → validated)
    protected function isFieldWorker(): bool
    {
        return in_array(strtolower($this->apiUser['role'] ?? ''), ['bhw', 'encoder', 'bns']);
    }

    protected function userBarangay(): ?string
    {
        return $this->apiUser['barangay'] ?? null;
    }

    protected function userId(): int
    {
        return (int) ($this->apiUser['user_id'] ?? 0);
    }
}
