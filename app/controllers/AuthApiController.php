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
            $this->error('Invalid credentials or account is inactive', 400);
        }

        // Admin requires email OTP
        if (strtolower($user['role']) === 'admin') {
            $email1 = $user['email'] ?? null;
            $email2 = $user['email_secondary'] ?? null;

            if (!empty($email1) || !empty($email2)) {
                $otp      = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otpToken = bin2hex(random_bytes(16));
                $expires  = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                $appName  = defined('APP_NAME') ? APP_NAME : 'NMS';

                $db = Database::getInstance();
                // Clean up any old challenges for this user
                $db->prepare('DELETE FROM api_otp_challenges WHERE user_id = ?')->execute([$user['id']]);
                $db->prepare('INSERT INTO api_otp_challenges (user_id, otp_token, otp_code, expires_at) VALUES (?, ?, ?, ?)')
                   ->execute([$user['id'], $otpToken, $otp, $expires]);

                $body    = \Mailer::otpEmail($otp, $appName);
                $subject = "{$appName} — Your login OTP";
                $sent    = false;
                if (!empty($email1)) \Mailer::send($email1, $user['full_name'], $subject, $body) && $sent = true;
                if (!empty($email2)) \Mailer::send($email2, $user['full_name'], $subject, $body) && $sent = true;

                if ($sent) {
                    $this->success([
                        'requires_otp' => true,
                        'otp_token'    => $otpToken,
                        'masked_email' => $this->maskEmail($email1 ?? $email2),
                    ], 'OTP sent to your registered email');
                    return;
                }
            }
            // No email configured — log in directly
        }

        $this->issueToken($user, $deviceName);
    }

    /**
     * POST /api/auth/verify-otp
     * Body: { otp_token, otp_code, device_name? }
     */
    public function verifyOtp(): void
    {
        $body      = $this->body();
        $otpToken  = trim($body['otp_token'] ?? '');
        $otpCode   = trim($body['otp_code']  ?? '');
        $deviceName = trim($body['device_name'] ?? 'Mobile');

        if (!$otpToken || !$otpCode) {
            $this->error('otp_token and otp_code are required');
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM api_otp_challenges WHERE otp_token = ? LIMIT 1');
        $stmt->execute([$otpToken]);
        $challenge = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$challenge) {
            $this->error('Invalid or expired OTP session', 400);
        }
        if (strtotime($challenge['expires_at']) < time()) {
            $db->prepare('DELETE FROM api_otp_challenges WHERE id = ?')->execute([$challenge['id']]);
            $this->error('OTP has expired. Please log in again.', 400);
        }
        if ($challenge['otp_code'] !== $otpCode) {
            $this->error('Incorrect OTP code', 400);
        }

        $db->prepare('DELETE FROM api_otp_challenges WHERE id = ?')->execute([$challenge['id']]);

        $userModel = new User();
        $user = $userModel->findById((int)$challenge['user_id']);
        if (!$user || !$user['is_active']) {
            $this->error('Account not found or inactive', 400);
        }

        $this->issueToken($user, $deviceName);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function issueToken(array $user, string $deviceName): void
    {
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        $db = Database::getInstance();
        $db->prepare('INSERT INTO api_tokens (user_id, token, device_name, expires_at) VALUES (?, ?, ?, ?)')
           ->execute([$user['id'], $token, $deviceName, $expiresAt]);

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

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2) + ['', ''];
        $visible = min(3, strlen($local));
        return substr($local, 0, $visible) . str_repeat('*', max(0, strlen($local) - $visible)) . '@' . $domain;
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
