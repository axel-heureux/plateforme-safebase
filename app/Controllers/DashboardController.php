<?php

require_once __DIR__ . '/BaseController.php';

class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->render('dashboard');
    }
}
