<?php

class BaseController
{
    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function error(string $message, int $status = 400): void
    {
        $this->json(['error' => $message], $status);
    }

    protected function input(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    protected function render(string $view, array $params = []): void
    {
        extract($params);
        require __DIR__ . '/../Views/' . $view . '.php';
    }
}
