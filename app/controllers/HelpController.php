<?php

namespace App\Controllers;

use Core\Controller;

class HelpController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->view('help/index');
    }
}
