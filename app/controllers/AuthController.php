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

    public function login(): void
    {
        if (Session::has('user_id')) {
            $this->redirect('/dashboard');
        }

        $this->validateCsrf();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            Session::flash('error', 'Username and password are required.');
            $this->view('auth/login', ['username' => $this->clean($username)], false);
            return;
        }

        $user = $this->userModel->authenticate($username, $password);

        if (!$user) {
            \ActivityLog::log('login_failed', "Failed login attempt for username: $username");
            Session::flash('error', 'Invalid username or password.');
            $this->view('auth/login', ['username' => $this->clean($username)], false);
            return;
        }

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
