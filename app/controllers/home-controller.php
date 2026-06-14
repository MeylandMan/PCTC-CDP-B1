<?php

require_once ROOT_PATH . '/core/controller.php';

class HomeController extends Controller
{
    public function index(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/dashboard');
        } else {
            $this->redirect('/auth/login');
        }
    }
}