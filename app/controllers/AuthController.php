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

        Session::regenerate();
        Session::set('user_id',          $user['id']);
        Session::set('user_name',        $user['full_name']);
        Session::set('user_role',        $user['role']);
        Session::set('user_barangay',    $user['barangay'] ?? '');
        Session::set('user_permissions', json_decode($user['permissions'] ?? '[]', true) ?: []);

        // BNS and Midwife are app-only — block web login
        if (in_array(strtolower($user['role']), ['bns', 'midwife', 'bhw'])) {
            Session::flash('error', 'Your account is for the mobile app only. Please use the NMS Mobile app.');
            $this->view('auth/login', ['username' => $this->clean($username)], false);
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
