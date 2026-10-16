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

    protected function rateLimit(int $maxRequests = 60, int $windowSeconds = 60): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = 'api_rl:' . md5($ip);
        if (function_exists('apcu_fetch')) {
            $count = (int)(apcu_fetch($key) ?: 0) + 1;
            if ($count === 1) apcu_store($key, 1, $windowSeconds);
            else              apcu_store($key, $count, apcu_fetch($key . '_ttl') ?: $windowSeconds);
            apcu_store($key . '_ttl', $windowSeconds, $windowSeconds);
            if ($count > $maxRequests) {
                header('Retry-After: ' . $windowSeconds);
                $this->error('Too many requests. Please slow down.', 429);
            }
        } else {
            $file = sys_get_temp_dir() . '/nms_rl_' . md5($ip);
            $data = file_exists($file) ? json_decode(file_get_contents($file), true) : null;
            if (!$data || time() > $data['reset']) $data = ['count' => 0, 'reset' => time() + $windowSeconds];
            $data['count']++;
            file_put_contents($file, json_encode($data), LOCK_EX);
            if ($data['count'] > $maxRequests) {
                header('Retry-After: ' . $windowSeconds);
                $this->error('Too many requests. Please slow down.', 429);
            }
        }
    }

    protected function requireApiAuth(): void
    {
        $this->rateLimit(120, 60); // 120 API requests per minute per IP
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

    protected function logActivity(string $action, string $description = ''): void
    {
        \ActivityLog::log(
            'mobile_' . $action,
            $description,
            $this->userId(),
            $this->apiUser['full_name'] ?? $this->apiUser['username'] ?? null
        );
    }
}
