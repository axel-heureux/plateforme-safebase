<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../../Core/Database.php';

class HealthController extends BaseController
{
    public function index(): void
    {
        try {
            $pdo = \Database::getInstance()->getConnection();
            $pdo->query('SELECT 1');
            $this->json([
                'status' => 'ok',
                'service' => 'safebase',
                'database' => 'connected',
            ]);
        } catch (Throwable $exception) {
            $this->json([
                'status' => 'error',
                'service' => 'safebase',
                'database' => 'unavailable',
            ], 503);
        }
    }
}
