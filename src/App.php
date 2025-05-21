<?php
namespace SudGipAuth;

use Sud\Gip\Api\SudGIPAuth;
use SudGipAuth\Routes\Router;
use SudGipAuth\Controllers\AuthController;

class App
{
    private $auth;
    private $router;

    public function __construct()
    {
        // 初始化认证服务
        $appId = $_ENV['APP_ID'];
        $appSecret = $_ENV['APP_SECRET'];
        $this->auth = new SudGIPAuth($appId, $appSecret);

        // 初始化路由
        $this->router = new Router();

        // 加载路由定义
        $routes = require_once __DIR__ . '/Routes/routes.php';
        $routes($this->router, $this);
    }

    public function run()
    {
        // 设置响应头
        header('Content-Type: application/json');

        // 执行路由
        $this->router->dispatch();
    }

    public function getAuthService()
    {
        return $this->auth;
    }
}    