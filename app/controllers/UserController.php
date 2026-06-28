<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\User;

class UserController extends Controller
{
    private User $model;

    public function __construct()
    {
        $this->model = new User();
    }

    public function index(): void
    {
        $this->requireAdmin();
        $users = $this->model->getAllUsers();
        $this->view('users/index', ['users' => $users]);
    }

    public function create(): void
    {
        $this->requireAdmin();

        if ($this->isPost()) {
            $this->validateCsrf();

            $username    = trim($_POST['username'] ?? '');
            $password    = $_POST['password'] ?? '';
            $fullName    = trim($_POST['full_name'] ?? '');
            $role        = trim($_POST['role'] ?? '');
            $barangay    = trim($_POST['barangay'] ?? '');
            $permissions = array_keys(array_filter($_POST['permissions'] ?? [], fn($v) => $v == '1'));
            $errors      = [];

            if (empty($username))                           $errors[] = 'Username is required.';
            if (empty($password) || strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
            if (empty($fullName))                           $errors[] = 'Full name is required.';
            if (empty($role))                               $errors[] = 'Role is required.';

            if (!empty($errors)) {
                Session::flash('error', implode('<br>', $errors));
                $this->view('users/create', ['data' => $_POST]);
                return;
            }

            if ($this->model->findByUsername($username)) {
                Session::flash('error', "Username '{$username}' is already taken.");
                $this->view('users/create', ['data' => $_POST]);
                return;
            }

            $this->model->createUser([
                'username'    => $username,
                'password'    => $password,
                'full_name'   => $fullName,
                'role'        => $role,
                'barangay'    => $barangay ?: null,
                'permissions' => $permissions,
            ]);

            Session::flash('success', "User '{$username}' created successfully.");
            $this->redirect('/users');
        }

        $this->view('users/create', ['data' => []]);
    }

    public function edit(string $id): void
    {
        $this->requireAdmin();
        $id   = (int)$id;
        $user = $this->model->findById($id);

        if (!$user) { $this->redirect('/users'); }

        if ($this->isPost()) {
            $this->validateCsrf();
            $permissions = array_keys(array_filter($_POST['permissions'] ?? [], fn($v) => $v == '1'));
            $this->model->updateUser($id, [
                'full_name'   => trim($_POST['full_name'] ?? ''),
                'role'        => trim($_POST['role'] ?? $user['role']),
                'barangay'    => trim($_POST['barangay'] ?? '') ?: null,
                'password'    => $_POST['password'] ?? '',
                'permissions' => $permissions,
            ]);
            Session::flash('success', 'User updated.');
            $this->redirect('/users');
        }

        $this->view('users/edit', ['user' => $user]);
    }

    public function delete(string $id): void
    {
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/users'); }
        $this->validateCsrf();

        $id   = (int)$id;
        if ($id === (int)Session::get('user_id')) {
            Session::flash('error', 'You cannot deactivate your own account.');
            $this->redirect('/users');
            return;
        }

        $user = $this->model->findById($id);
        if ($user) {
            $this->model->deactivate($id);
            Session::flash('success', 'User deactivated.');
        }
        $this->redirect('/users');
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/users'); }
        $this->validateCsrf();

        $id = (int)$id;
        if ($id === (int)Session::get('user_id')) {
            Session::flash('error', 'You cannot delete your own account.');
            $this->redirect('/users');
            return;
        }

        $user = $this->model->findById($id);
        if ($user) {
            $this->model->deleteUser($id);
            Session::flash('success', "User '{$user['username']}' permanently deleted.");
        }
        $this->redirect('/users');
    }

    public function activate(string $id): void
    {
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/users'); }
        $this->validateCsrf();

        $id  = (int)$id;
        $user = $this->model->findById($id);
        if ($user) {
            $this->model->activate($id);
            Session::flash('success', 'User activated.');
        }
        $this->redirect('/users');
    }
}
