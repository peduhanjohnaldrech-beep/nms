<?php
namespace Core;

class Router
{
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, string $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function put(string $path, string $handler): void
    {
        $this->routes['PUT'][$path] = $handler;
    }

    public function delete(string $path, string $handler): void
    {
        $this->routes['DELETE'][$path] = $handler;
    }

    public function patch(string $path, string $handler): void
    {
        $this->routes['PATCH'][$path] = $handler;
    }

    public function dispatch(): void
    {
        // Handle CORS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type');
            http_response_code(204);
            exit;
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip base path from URI
        $basePath = parse_url(APP_URL, PHP_URL_PATH);
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = '/' . trim($uri, '/');
        if ($uri === '') $uri = '/';

        // Try exact match first
        if (isset($this->routes[$method][$uri])) {
            $this->call($this->routes[$method][$uri], []);
            return;
        }

        // Try parameterized routes
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = preg_replace('/\{(\w+)\}/', '([^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';
            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);
                $this->call($handler, $matches);
                return;
            }
        }

        // 404
        http_response_code(404);
        View::render('errors/404', [], false);
    }

    private function call(string $handler, array $params): void
    {
        [$class, $method] = explode('@', $handler);
        $fqcn = 'App\\Controllers\\' . $class;
        $controller = new $fqcn();
        $controller->$method(...$params);
    }
}
