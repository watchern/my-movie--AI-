<?php
use think\facade\Env;

// 数据库配置
return [
    // 默认数据库连接
    'default' => Env::get('DATABASE_TYPE', 'sqlite'),

    // 数据库连接信息
    'connections' => [
        'mysql' => [
            'type' => Env::get('DATABASE_TYPE', 'mysql'),
            'hostname' => Env::get('DATABASE_HOSTNAME', '127.0.0.1'),
            'database' => Env::get('DATABASE_DATABASE', 'moive_app'),
            'username' => Env::get('DATABASE_USERNAME', 'root'),
            'password' => Env::get('DATABASE_PASSWORD', ''),
            'hostport' => Env::get('DATABASE_HOSTPORT', '3306'),
            'params' => [],
            'charset' => Env::get('DATABASE_CHARSET', 'utf8mb4'),
            'prefix' => Env::get('DATABASE_PREFIX', ''),
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
            'database' => Env::get('DATABASE_SQLITE_PATH', dirname(__DIR__, 2) . '/database/database.sqlite'),
            'prefix' => Env::get('DATABASE_PREFIX', ''),
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
