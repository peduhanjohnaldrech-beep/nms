<?php

namespace App\Controllers;

use Core\ApiController;
use Core\Database;

/**
 * GET /api/health
 * Returns system health status — database connectivity, PHP version, uptime.
 * Used for monitoring and deployment health checks.
 */
class HealthController extends ApiController
{
    public function check(): void
    {
        $db_ok   = false;
        $db_ms   = null;

        try {
            $t0    = microtime(true);
            Database::getInstance()->query('SELECT 1');
            $db_ms = round((microtime(true) - $t0) * 1000, 2);
            $db_ok = true;
        } catch (\Throwable $e) {
            error_log('[NMS] Health check DB error: ' . $e->getMessage());
        }

        $status = $db_ok ? 'ok' : 'degraded';
        $code   = $db_ok ? 200  : 503;

        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'status'     => $status,
            'timestamp'  => date('c'),
            'php'        => PHP_VERSION,
            'app'        => APP_NAME ?? 'NMS',
            'env'        => APP_ENV  ?? 'unknown',
            'database'   => [
                'connected'    => $db_ok,
                'response_ms'  => $db_ms,
            ],
        ]);
        exit;
    }
}
