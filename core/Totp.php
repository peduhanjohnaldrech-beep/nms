<?php

namespace Core;

class Totp
{
    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const DIGITS = 6;
    private const PERIOD = 30;

    public static function generateSecret(int $length = 16): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Verify a 6-digit code against a secret.
     * Accepts codes from ±$window time-steps to handle clock skew.
     */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== self::DIGITS) return false;

        $counter = (int)floor(time() / self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::hotp($secret, $counter + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build an otpauth:// URI for QR code generation.
     */
    public static function getUri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret='    . rawurlencode($secret)
            . '&issuer='    . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . self::DIGITS
            . '&period='    . self::PERIOD;
    }

    // ----------------------------------------------------------------
    // Internal helpers
    // ----------------------------------------------------------------

    private static function hotp(string $secret, int $counter): string
    {
        $key    = self::base32Decode($secret);
        $msg    = pack('N*', 0) . pack('N*', $counter);   // 8-byte big-endian counter
        $hash   = hash_hmac('sha1', $msg, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $code   = (
            ((ord($hash[$offset])     & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) <<  8) |
             (ord($hash[$offset + 3]) & 0xff)
        ) % (10 ** self::DIGITS);
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $secret): string
    {
        $secret  = strtoupper(preg_replace('/\s+/', '', $secret));
        $binary  = '';
        $buffer  = 0;
        $bitsLeft = 0;
        foreach (str_split($secret) as $char) {
            $val = strpos(self::BASE32, $char);
            if ($val === false) continue;
            $buffer    = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binary   .= chr(($buffer >> $bitsLeft) & 0xff);
            }
        }
        return $binary;
    }
}
