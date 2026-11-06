<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\User;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function showLogin(): void
    {
        if (Session::has('user_id')) {
            $this->redirect('/dashboard');
        }
        $this->view('auth/login', [], false);
    }

    private function rateLimitKey(string $username): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return 'login_fail:' . md5($ip . ':' . strtolower($username));
    }

    private function isLockedOut(string $username): bool
    {
        $key = $this->rateLimitKey($username);
        if (function_exists('apcu_fetch')) {
            $attempts = apcu_fetch($key) ?: 0;
            return $attempts >= 5;
        }
        $file = sys_get_temp_dir() . '/' . $key;
        if (!file_exists($file)) return false;
        $data = json_decode(file_get_contents($file), true);
        if (!$data || time() > $data['expires']) { @unlink($file); return false; }
        return $data['count'] >= 5;
    }

    private function recordFailedAttempt(string $username): int
    {
        $key = $this->rateLimitKey($username);
        if (function_exists('apcu_fetch')) {
            $attempts = (int)(apcu_fetch($key) ?: 0) + 1;
            apcu_store($key, $attempts, 900); // 15 minutes
            return $attempts;
        }
        $file = sys_get_temp_dir() . '/' . $key;
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : null;
        if (!$data || time() > ($data['expires'] ?? 0)) $data = ['count' => 0, 'expires' => time() + 900];
        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        return $data['count'];
    }

    private function clearFailedAttempts(string $username): void
    {
        $key = $this->rateLimitKey($username);
        if (function_exists('apcu_delete')) { apcu_delete($key); return; }
        @unlink(sys_get_temp_dir() . '/' . $key);
    }

    public function login(): void
    {
        if (Session::has('user_id')) {
            $this->redirect('/dashboard');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate CSRF — if it fails the session likely expired; re-show login gracefully
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!\Core\Session::validateCsrf($csrfToken)) {
            Session::flash('error', 'Page expired. Please try again.');
            $this->view('auth/login', ['username' => $this->clean($username)], false);
            return;
        }

        if (empty($username) || empty($password)) {
            Session::flash('error', 'Username and password are required.');
            $this->view('auth/login', ['username' => $this->clean($username)], false);
            return;
        }

        // Brute force protection — lock out after 5 failed attempts for 15 minutes
        if ($this->isLockedOut($username)) {
            Session::flash('error', 'Too many failed login attempts. Please try again in 15 minutes.');
            $this->view('auth/login', ['username' => $this->clean($username)], false);
            return;
        }

        $user = $this->userModel->authenticate($username, $password);

        if (!$user) {
            $attempts = $this->recordFailedAttempt($username);
            $remaining = max(0, 5 - $attempts);
            \ActivityLog::log('login_failed', "Failed login attempt for username: $username (attempt $attempts)");
            $msg = $remaining > 0
                ? "Invalid username or password. $remaining attempt(s) remaining."
                : 'Too many failed login attempts. Please try again in 15 minutes.';
            Session::flash('error', $msg);
            $this->view('auth/login', ['username' => $this->clean($username)], false);
            return;
        }

        $this->clearFailedAttempts($username);

        // Trusted device check — unrecognized device triggers email OTP
        if (strtolower($user['role']) === 'admin') {
            $storedToken = $user['device_token'] ?? null;
            $cookieToken = $_COOKIE['nms_device_token'] ?? null;
            $db          = \Core\Database::getInstance();

            if ($storedToken === null) {
                // First login ever — register this device automatically
                $token = bin2hex(random_bytes(32));
                $db->prepare("UPDATE users SET device_token = ? WHERE id = ?")->execute([$token, $user['id']]);
                setcookie('nms_device_token', $token, time() + 365 * 24 * 3600, '/', '', false, true);
            } elseif (!$cookieToken || !hash_equals($storedToken, $cookieToken)) {
                // Unrecognized device — send OTP to admin's email
                $email1 = $user['email'] ?? null;
                $email2 = $user['email_secondary'] ?? null;
                if (empty($email1) && empty($email2)) {
                    \ActivityLog::log('login_failed', "Admin login from unrecognized device, no email on file: $username");
                    Session::flash('error', 'Unrecognized device. No email is set for this account — ask your administrator to add one in User Management.');
                    $this->view('auth/login', ['username' => $this->clean($username)], false);
                    return;
                }

                $otp     = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $appName = defined('APP_NAME') ? APP_NAME : 'NMS';
                $body    = \Mailer::otpEmail($otp, $appName);
                $subject = "{$appName} — Login Verification Code";
                $sent    = false;
                $sentTo  = [];

                if (!empty($email1)) {
                    if (\Mailer::send($email1, $user['full_name'], $subject, $body)) {
                        $sent = true; $sentTo[] = $email1;
                    }
                }
                if (!empty($email2)) {
                    if (\Mailer::send($email2, $user['full_name'], $subject, $body)) {
                        $sent = true; $sentTo[] = $email2;
                    }
                }

                if (!$sent) {
                    Session::flash('error', 'Could not send verification email. Check SMTP settings or contact your administrator.');
                    $this->view('auth/login', ['username' => $this->clean($username)], false);
                    return;
                }

                Session::set('otp_code',    $otp);
                Session::set('otp_user_id', $user['id']);
                Session::set('otp_emails',  $sentTo);
                Session::set('otp_expires', time() + 600);

                \ActivityLog::log('login_otp_sent', "OTP sent to " . implode(', ', $sentTo) . " for admin login: $username");

                $maskedList = array_map(function($e) {
                    $at = strpos($e, '@');
                    return substr($e, 0, 2) . str_repeat('*', max(1, $at - 2)) . substr($e, $at);
                }, $sentTo);
                Session::flash('info', "A verification code was sent to <strong>" . implode('</strong> and <strong>', $maskedList) . "</strong>.");
                $this->redirect('/otp-verify');
                return;
            }
        }

        $this->completeLogin($user);
    }

    // ----------------------------------------------------------------
    // Email OTP challenge (unrecognized device)
    // ----------------------------------------------------------------

    public function showOtpVerify(): void
    {
        if (!Session::has('otp_user_id')) {
            $this->redirect('/login');
        }
        $emails  = Session::get('otp_emails', []);
        $masked  = array_map(function($e) {
            $at = strpos($e, '@');
            return $at !== false ? substr($e, 0, 2) . str_repeat('*', max(1, $at - 2)) . substr($e, $at) : $e;
        }, $emails);
        $this->view('auth/otp_verify', ['maskedEmails' => $masked], false);
    }

    public function verifyOtp(): void
    {
        if (!Session::has('otp_user_id')) {
            $this->redirect('/login');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!\Core\Session::validateCsrf($csrfToken)) {
            Session::flash('error', 'Page expired. Please try again.');
            $this->redirect('/otp-verify');
            return;
        }

        $storedOtp = Session::get('otp_code');
        $expires   = (int)Session::get('otp_expires');
        $userId    = (int)Session::get('otp_user_id');

        if (time() > $expires) {
            Session::remove('otp_code'); Session::remove('otp_user_id');
            Session::remove('otp_expires'); Session::remove('otp_email');
            Session::flash('error', 'Verification code expired. Please log in again.');
            $this->redirect('/login');
            return;
        }

        $code = trim($_POST['otp_code'] ?? '');
        if (!hash_equals($storedOtp, $code)) {
            Session::flash('error', 'Incorrect verification code. Please try again.');
            $this->redirect('/otp-verify');
            return;
        }

        // OTP passed — register this device as the new trusted device
        $db    = \Core\Database::getInstance();
        $token = bin2hex(random_bytes(32));
        $db->prepare("UPDATE users SET device_token = ? WHERE id = ?")->execute([$token, $userId]);
        setcookie('nms_device_token', $token, time() + 365 * 24 * 3600, '/', '', false, true);

        Session::remove('otp_code'); Session::remove('otp_user_id');
        Session::remove('otp_expires'); Session::remove('otp_emails');

        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            Session::flash('error', 'Account not found or deactivated.');
            $this->redirect('/login');
            return;
        }

        \ActivityLog::log('login_device_verified', "New device registered via OTP for user ID: {$userId}");
        $this->completeLogin($user);
    }

    public function resendOtp(): void
    {
        if (!Session::has('otp_user_id')) {
            $this->redirect('/login');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!\Core\Session::validateCsrf($csrfToken)) {
            Session::flash('error', 'Page expired.');
            $this->redirect('/otp-verify');
            return;
        }

        $userId = (int)Session::get('otp_user_id');
        $emails = Session::get('otp_emails', []);
        $db     = \Core\Database::getInstance();
        $stmt   = $db->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row    = $stmt->fetch(\PDO::FETCH_ASSOC);

        $otp     = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $appName = defined('APP_NAME') ? APP_NAME : 'NMS';
        $body    = \Mailer::otpEmail($otp, $appName);
        foreach ($emails as $email) {
            \Mailer::send($email, $row['full_name'] ?? '', "{$appName} — Login Verification Code", $body);
        }
        Session::set('otp_code',    $otp);
        Session::set('otp_expires', time() + 600);

        Session::flash('info', 'A new verification code has been sent.');
        $this->redirect('/otp-verify');
    }

    // ----------------------------------------------------------------
    // Shared login finisher
    // ----------------------------------------------------------------

    private function completeLogin(array $user): void
    {
        Session::regenerate();
        Session::set('user_id',          $user['id']);
        Session::set('user_name',        $user['full_name']);
        Session::set('user_role',        $user['role']);
        Session::set('user_barangay',    $user['barangay'] ?? '');
        Session::set('user_permissions', json_decode($user['permissions'] ?? '[]', true) ?: []);

        // BNS and BHW are app-only — block web login
        if (in_array(strtolower($user['role']), ['bhw'])) {
            Session::flash('error', 'Your account is for the mobile app only. Please use the NMS Mobile app.');
            $this->view('auth/login', [], false);
            return;
        }

        \ActivityLog::log('login', "User logged in: {$user['full_name']} ({$user['role']})");

        Session::flash('success', 'Welcome back, ' . $user['full_name'] . '!');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        \ActivityLog::log('logout', 'User logged out');
        Session::destroy();
        Session::start();
        Session::flash('success', 'You have been logged out.');
        $this->redirect('/login');
    }
}
