<?php

/**
 * Routeur minimal : associe une méthode HTTP + un pattern d'URL
 * à une méthode de controller (ex: DbConnectionController::index).
 */
class Router
{
    private array $routes = [];

    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function patch(string $pattern, array $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, array $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    private function add(string $method, string $pattern, array $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        // PATCH/DELETE arrivent parfois en POST avec _method dans le body
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $regex = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $route['pattern']);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $path, $matches)) {
                array_shift($matches);
                [$class, $action] = $route['handler'];

                require_once __DIR__ . '/../App/Controllers/' . $class . '.php';
                $controller = new $class();
                call_user_func_array([$controller, $action], $matches);
                return;
            }
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Route introuvable : ' . $method . ' ' . $path]);
    }
}
