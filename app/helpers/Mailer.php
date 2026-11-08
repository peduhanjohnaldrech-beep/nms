<?php

class Mailer
{
    /**
     * Send an HTML email. Uses PHPMailer+SMTP if SMTP_HOST is set,
     * otherwise falls back to PHP mail().
     */
    public static string $lastError = '';

    public static function send(string $to, string $toName, string $subject, string $body): bool
    {
        self::$lastError = '';
        $fromName = getenv('SMTP_FROM_NAME') ?: ($_ENV['SMTP_FROM_NAME'] ?? (defined('APP_NAME') ? APP_NAME : 'NMS'));

        // ── 1. Resend HTTP API (works even when SMTP ports are blocked) ──
        $resendKey = getenv('RESEND_API_KEY') ?: ($_ENV['RESEND_API_KEY'] ?? '');
        if ($resendKey) {
            $from = getenv('SMTP_FROM') ?: ($_ENV['SMTP_FROM'] ?? 'onboarding@resend.dev');
            $payload = json_encode([
                'from'    => "{$fromName} <{$from}>",
                'to'      => [$to],
                'subject' => $subject,
                'html'    => $body,
            ]);
            $ch = curl_init('https://api.resend.com/emails');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $resendKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_TIMEOUT    => 15,
            ]);
            $response = curl_exec($ch);
            $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($status === 200 || $status === 201) return true;
            self::$lastError = "Resend API error (HTTP {$status}): {$response}";
            error_log('Mailer (Resend): ' . self::$lastError);
            return false;
        }

        // ── 2. SMTP via PHPMailer ────────────────────────────────────────
        $smtpHost = getenv('SMTP_HOST') ?: ($_ENV['SMTP_HOST'] ?? '');
        $smtpUser = getenv('SMTP_USER') ?: ($_ENV['SMTP_USER'] ?? '');
        $smtpPass = getenv('SMTP_PASS') ?: ($_ENV['SMTP_PASS'] ?? '');
        $from     = getenv('SMTP_FROM')  ?: ($_ENV['SMTP_FROM'] ?? 'noreply@localhost');
        $port     = (int)(getenv('SMTP_PORT') ?: ($_ENV['SMTP_PORT'] ?? 587));

        if (!$smtpHost || !$smtpUser) {
            self::$lastError = 'No mail driver configured (set RESEND_API_KEY or SMTP_HOST in .env)';
            error_log('Mailer: ' . self::$lastError);
            return false;
        }
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            self::$lastError = 'PHPMailer not found — run: composer install --no-dev';
            error_log('Mailer: ' . self::$lastError);
            return false;
        }
        $sock = @fsockopen($smtpHost, $port, $errno, $errstr, 3);
        if (!$sock) {
            self::$lastError = "SMTP unreachable ({$smtpHost}:{$port}) — {$errstr}";
            error_log('Mailer: ' . self::$lastError);
            return false;
        }
        fclose($sock);
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $port;
            $mail->Timeout    = 10;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</h2>', '</h3>'], "\n", $body));
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            self::$lastError = $e->getMessage();
            error_log('Mailer (SMTP): ' . $e->getMessage());
            return false;
        }
    }

    public static function otpEmail(string $otp, string $appName = 'NMS'): string
    {
        return "
        <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:8px;'>
            <h3 style='color:#1e293b;margin-bottom:8px;'>{$appName} Login Verification</h3>
            <p style='color:#64748b;margin-bottom:24px;'>Use the code below to complete your sign-in. It expires in <strong>10 minutes</strong>.</p>
            <div style='text-align:center;background:#f1f5f9;border-radius:8px;padding:20px 0;margin-bottom:24px;'>
                <span style='font-size:2.5rem;font-weight:700;letter-spacing:0.4em;color:#2563eb;'>{$otp}</span>
            </div>
            <p style='color:#94a3b8;font-size:0.85rem;'>If you did not attempt to log in, you can safely ignore this email. Do not share this code with anyone.</p>
        </div>";
    }
}
