<?php
namespace SudGipAuth\Routes;

use SudGipAuth\Http\Response;

class Router
{
    private $routes = [];

    public function get($path, $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post($path, $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $originalBody): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (!isset($this->routes[$method][$path])) {
            $allowed = [];
            foreach ($this->routes as $verb => $paths) {
                if (isset($paths[$path])) {
                    $allowed[] = $verb;
                }
            }
            if ($allowed) {
                header('Allow: ' . implode(', ', $allowed));
            }
            Response::error($allowed ? 405 : 404, $allowed ? 'Method Not Allowed' : 'Not Found');
            return;
        }

        $body = [];
        if ($method === 'POST') {
            $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
            if ($contentType !== 'application/json') {
                Response::error(400, 'Expected JSON');
                return;
            }
            try {
                // Keep unknown fields for forward compatibility; each handler reads only its inputs.
                $object = json_decode($originalBody, false, 512, JSON_THROW_ON_ERROR);
                if (!is_object($object)) {
                    Response::error(400, 'Expected JSON object');
                    return;
                }
                $body = (array) $object;
            } catch (\JsonException $exception) {
                Response::error(400, 'Invalid JSON request');
                return;
            }
        }
        call_user_func($this->routes[$method][$path], $_GET, $body);
    }
}
