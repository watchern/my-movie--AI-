<?php
namespace think;

// CORS 头
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// 处理 OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 检查是否已安装
$vendorExists = file_exists(__DIR__ . '/../vendor/autoload.php');
$envExists = file_exists(__DIR__ . '/../.env');

if (!$vendorExists || !$envExists) {
    // 未安装，跳转到安装向导
    header('Location: install.php');
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

// 使用 symfony/dotenv 加载 .env 文件
use Symfony\Component\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

// 执行应用
$app = new App();
$http = new Http($app);
$response = $http->run();
$response->send();
