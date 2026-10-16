<?php

/**
 * ReportCache
 *
 * Lightweight two-tier cache for heavy report queries.
 *   Tier 1: APCu (in-process, fastest) — used when the extension is loaded.
 *   Tier 2: File-based (storage/cache/reports/) — always available as fallback.
 *
 * Default TTL: 15 minutes.  Cache is invalidated on any write that modifies
 * assessment or beneficiary data (call ReportCache::invalidate() from those
 * controllers).
 */
class ReportCache
{
    private const TTL = 900; // seconds (15 min)

    private static function cacheDir(): string
    {
        return BASE_PATH . '/storage/cache/reports';
    }

    /**
     * Returns the cached value for $key, or null if not cached / expired.
     */
    public static function get(string $key): mixed
    {
        // Tier 1: APCu
        if (function_exists('apcu_fetch')) {
            $val = apcu_fetch($key, $ok);
            if ($ok) return $val;
        }

        // Tier 2: File
        $file = self::cacheDir() . '/' . md5($key) . '.cache';
        if (file_exists($file) && (time() - filemtime($file)) < self::TTL) {
            $raw = file_get_contents($file);
            return $raw !== false ? unserialize($raw) : null;
        }

        return null;
    }

    /**
     * Stores $value under $key for TTL seconds.
     */
    public static function set(string $key, mixed $value): void
    {
        // Tier 1: APCu
        if (function_exists('apcu_store')) {
            apcu_store($key, $value, self::TTL);
        }

        // Tier 2: File (always write so the file TTL is available on next request
        //                 even if APCu is cleared by a web-server restart)
        $dir = self::cacheDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($dir . '/' . md5($key) . '.cache', serialize($value));
    }

    /**
     * Invalidates ALL report cache entries.
     * Call this after any write that modifies assessment or beneficiary data.
     */
    public static function invalidate(): void
    {
        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }
        $dir = self::cacheDir();
        if (is_dir($dir)) {
            foreach (glob($dir . '/*.cache') ?: [] as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Builds a deterministic cache key from the query parameters.
     */
    public static function key(string ...$parts): string
    {
        return 'report:' . md5(implode('|', $parts));
    }
}
