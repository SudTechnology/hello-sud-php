<?php
namespace SudGipAuth\Routes;

class Router
{
    private $routes = [];

    public function get($path, $handler)
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post($path, $handler)
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // 检查路由是否存在
        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];
            
            // 处理GET请求参数
            $queryParams = $_GET;
            
            // 处理POST请求体
            $requestBody = [];
            if ($method === 'POST') {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
                if (strpos($contentType, 'application/json') !== false) {
                    $requestBody = json_decode(file_get_contents('php://input'), true);
                } else {
                    $requestBody = $_POST;
                }
            }
            
            // 执行处理函数
            call_user_func($handler, $queryParams, $requestBody);
            return;
        }
        
        // 路由不存在
        http_response_code(404);
        echo json_encode([
            "ret_code" => 1,
            "ret_msg" => "Not Found"
        ]);
    }
}    