<?php
use think\facade\Env;

// 应用配置
return [
    // 应用名称
    'app_name' => Env::get('app.app_name', '影视系统'),

    // 应用调试模式
    'app_debug' => Env::get('app.debug', true),

    // 应用时区
    'default_timezone' => 'Asia/Shanghai',

    // 应用地址
    'app_host' => Env::get('app.host', 'http://localhost'),

    // 跨域配置
    'cors' => [
        'on' => true,
        'origin' => '*',
        'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    ],
];
