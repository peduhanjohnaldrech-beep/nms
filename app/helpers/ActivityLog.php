<?php

class ActivityLog
{
    public static function log(string $action, string $description = '', ?int $userId = null, ?string $userName = null): void
    {
        try {
            $db   = \Core\Database::getInstance();
            $stmt = $db->prepare(
                "INSERT INTO activity_logs (user_id, user_name, action, description, ip_address)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $userId   ?? \Core\Session::get('user_id'),
                $userName ?? \Core\Session::get('user_name'),
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Exception $e) {
            // fail silently — never break the app for logging
        }
    }
}
