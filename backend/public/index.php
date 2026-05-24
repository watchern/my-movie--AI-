<?php
namespace think;

// 检查是否已安装
$vendorExists = file_exists(__DIR__ . '/vendor/autoload.php');
$envExists = file_exists(__DIR__ . '/../.env');

if (!$vendorExists || !$envExists) {
    // 未安装，跳转到安装向导
    header('Location: install.php');
    exit;
}

require __DIR__ . '/vendor/autoload.php';

// 执行应用
(new App())->run();
