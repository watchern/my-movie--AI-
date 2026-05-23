<?php
use think\facade\Env;

// 数据库配置
return [
    // 默认数据库连接
    'default' => Env::get('database.type', 'mysql'),

    // 数据库连接信息
    'connections' => [
        'mysql' => [
            'type' => Env::get('database.type', 'mysql'),
            'hostname' => Env::get('database.hostname', '127.0.0.1'),
            'database' => Env::get('database.database', 'moive_app'),
            'username' => Env::get('database.username', 'root'),
            'password' => Env::get('database.password', ''),
            'hostport' => Env::get('database.hostport', '3306'),
            'params' => [],
            'charset' => Env::get('database.charset', 'utf8mb4'),
            'prefix' => Env::get('database.prefix', ''),
            'deploy' => 0,
            'rw_separate' => false,
            'master_num' => 1,
            'slave_no' => '',
            'break_reconnect' => false,
            'fields_strict' => true,
            'break_match_str' => [],
        ],
        'sqlite' => [
            'type' => 'sqlite',
            'database' => Env::get('database.sqlite_path', dirname(__DIR__) . '/database/database.sqlite'),
            'prefix' => Env::get('database.prefix', ''),
            'charset' => 'utf8',
        ],
    ],

    // 路由配置
    'route' => [
        'route_on' => true,
        'route_complete_match' => false,
        'route_config_file' => ['route'],
        'route_annotation' => false,
    ],
];
