<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/LogModel.php';

class LogController extends BaseController
{
    private LogModel $logs;

    public function __construct()
    {
        $this->logs = new LogModel();
    }

    // GET /api/logs
    public function index(): void
    {
        $this->json($this->logs->getAll([], ['created_at DESC'], 100));
    }
}
