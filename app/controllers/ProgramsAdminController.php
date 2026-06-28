<?php

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\Program;

class ProgramsAdminController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        $programs = (new Program())->getAll();
        $this->view('programs_admin/index', ['programs' => $programs]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        if ($this->isPost()) {
            $this->validateCsrf();

            $code = strtoupper(trim($_POST['code'] ?? ''));
            $name = trim($_POST['name'] ?? '');

            if (!$code || !$name) {
                Session::flash('error', 'Code and name are required.');
                $this->view('programs_admin/form', ['data' => $_POST, 'editing' => false]);
                return;
            }

            (new Program())->insert([
                'code'        => $code,
                'name'        => $name,
                'description' => trim($_POST['description'] ?? ''),
                'icon'        => trim($_POST['icon'] ?? 'bi-clipboard-check'),
                'color'       => trim($_POST['color'] ?? 'primary'),
                'type'        => $_POST['type'] ?? 'generic',
                'is_active'   => 1,
                'sort_order'  => (int)($_POST['sort_order'] ?? 0),
            ]);

            \ActivityLog::log('program_create', "Created program: $code — $name");
            Session::flash('success', "Program '$name' created.");
            $this->redirect('/programs-admin');
        }

        $this->view('programs_admin/form', ['data' => [], 'editing' => false]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $this->requireAdmin();

        $program = (new Program())->findById((int)$id);
        if (!$program) { $this->redirect('/programs-admin'); }

        if ($this->isPost()) {
            $this->validateCsrf();

            (new Program())->update((int)$id, [
                'name'        => trim($_POST['name'] ?? $program['name']),
                'description' => trim($_POST['description'] ?? ''),
                'icon'        => trim($_POST['icon'] ?? 'bi-clipboard-check'),
                'color'       => trim($_POST['color'] ?? 'primary'),
                'type'        => $_POST['type'] ?? $program['type'],
                'is_active'   => isset($_POST['is_active']) ? 1 : 0,
                'sort_order'  => (int)($_POST['sort_order'] ?? 0),
            ]);

            \ActivityLog::log('program_update', "Updated program ID $id: {$program['code']}");
            Session::flash('success', 'Program updated.');
            $this->redirect('/programs-admin');
        }

        $this->view('programs_admin/form', ['data' => $program, 'editing' => true]);
    }

    public function toggle(string $id): void
    {
        $this->requireAuth();
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('/programs-admin'); }
        $this->validateCsrf();

        $program = (new Program())->findById((int)$id);
        if ($program) {
            $newState = $program['is_active'] ? 0 : 1;
            (new Program())->update((int)$id, ['is_active' => $newState]);
            Session::flash('success', 'Program ' . ($newState ? 'activated' : 'deactivated') . '.');
        }

        $this->redirect('/programs-admin');
    }
}
