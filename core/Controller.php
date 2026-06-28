<?php
namespace Core;

class Controller
{
    protected function view(string $view, array $data = [], bool $withLayout = true): void
    {
        View::render($view, $data, $withLayout);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . APP_URL . $path);
        exit;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function requireAuth(): void
    {
        if (!Session::has('user_id')) {
            $this->redirect('/login');
        }
    }

    protected function requireRole(array $roles): void
    {
        $this->requireAuth();
        $role = Session::get('user_role');
        if (strtolower($role) === 'admin') return;
        if (!in_array($role, $roles)) {
            Session::flash('error', 'Access denied.');
            $this->redirect('/dashboard');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (strtolower(Session::get('user_role')) !== 'admin') {
            Session::flash('error', 'Access denied. Admins only.');
            $this->redirect('/dashboard');
        }
    }

    protected function requirePermission(string $module): void
    {
        $this->requireAuth();
        if (strtolower(Session::get('user_role')) === 'admin') return;
        $permissions = Session::get('user_permissions', []);
        if (!in_array($module, (array)$permissions)) {
            Session::flash('error', 'You do not have permission to access this page.');
            $this->redirect('/dashboard');
        }
    }

    protected function hasPermission(string $module): bool
    {
        if (strtolower(Session::get('user_role')) === 'admin') return true;
        return in_array($module, (array)Session::get('user_permissions', []));
    }

    protected function validateCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrf($token)) {
            Session::flash('error', 'Invalid form token. Please try again.');
            $this->redirect('/dashboard');
        }
    }

    protected function clean(mixed $value): string
    {
        return htmlspecialchars(trim((string)($value ?? '')));
    }

    protected function currentUser(): array
    {
        return [
            'id'       => Session::get('user_id'),
            'name'     => Session::get('user_name'),
            'role'     => Session::get('user_role'),
            'barangay' => Session::get('user_barangay'),
        ];
    }

    protected function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
