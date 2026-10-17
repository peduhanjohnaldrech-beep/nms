<?php
namespace Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Use a custom session path so the system cron (sessionclean) doesn't wipe sessions mid-use
            $savePath = BASE_PATH . '/storage/sessions';
            if (!is_dir($savePath)) mkdir($savePath, 0700, true);
            session_save_path($savePath);
            ini_set('session.gc_maxlifetime', 28800);
            ini_set('session.gc_probability', 1);
            ini_set('session.gc_divisor', 100);
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
            session_set_cookie_params(['lifetime' => 28800, 'httponly' => true, 'samesite' => 'Lax', 'secure' => $isHttps]);
            session_start();
        }

        // Auto-logout after 8 hours of inactivity
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 28800) {
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        session_destroy();
    }

    public static function flash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }

    // CSRF
    public static function generateCsrf(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrf(string $token): bool
    {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    public static function csrfField(): string
    {
        $token = self::generateCsrf();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    public static function getAllFlash(): array
    {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
