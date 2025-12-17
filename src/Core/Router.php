<?php

namespace App\Core;

class Router
{
    protected array $routes = [];

    public function get($path, $callback)
    {
        $this->routes['get'][$path] = $callback;
    }

    public function post($path, $callback)
    {
        $this->routes['post'][$path] = $callback;
    }

    public function resolve()
    {
        $path = $this->getPath();
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $callback = $this->routes[$method][$path] ?? false;

        if ($callback === false) {
            http_response_code(404);
            echo "Not Found";
            return;
        }

        if (is_string($callback)) {
            return $this->renderView($callback);
        }

        if (is_array($callback)) {
            /** @var \App\Core\Controller $controller */
            $controller = new $callback[0]();
            $callback[0] = $controller;
        }

        return call_user_func($callback, $this->getRequest());
    }

    protected function getPath()
    {
        $path = $_GET['_url'] ?? '/';
        return '/' . ltrim($path, '/');
    }

    // Placeholder for View rendering to keep it simple for now
    protected function renderView($view)
    {
        include __DIR__ . "/../View/$view.php";
    }

    protected function getRequest()
    {
        return [
            'get' => $_GET,
            'post' => $_POST,
            'body' => file_get_contents('php://input')
        ];
    }
}
