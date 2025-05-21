<?php
use SudGipAuth\Controllers\AuthController;

return function($router, $app) {
    // 注册路由
    $router->get('/api/getCode', function($queryParams, $requestBody) use ($app) {
        $controller = new AuthController($app->getAuthService());
        $controller->getCode($queryParams);
    });

    $router->post('/api/getSSToken', function($queryParams, $requestBody) use ($app) {
        $controller = new AuthController($app->getAuthService());
        $controller->getSSToken($requestBody);
    });

    $router->post('/api/updateSSToken', function($queryParams, $requestBody) use ($app) {
        $controller = new AuthController($app->getAuthService());
        $controller->updateSSToken($requestBody);
    });
};