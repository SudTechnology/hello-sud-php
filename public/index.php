<?php
// 加载Composer自动加载器
require_once __DIR__ . '/../vendor/autoload.php';

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// 启动应用
try {
    $app = new SudGipAuth\App();
    $app->run();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "ret_code" => 1,
        "ret_msg" => "Internal Server Error",
        "error" => $e->getMessage()
    ]);
}    