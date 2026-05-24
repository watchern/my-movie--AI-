<?php
// +----------------------------------------------------------------------
// | 缓存配置
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    // 默认缓存驱动
    'default' => 'file',

    // 缓存连接配置
    'stores'  => [
        // 文件缓存
        'file' => [
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => '',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
        ],

        // Redis缓存
        'redis' => [
            'type'       => 'redis',
            'host'       => Env::get('cache.redis_host', '127.0.0.1'),
            'port'       => Env::get('cache.redis_port', 6379),
            'password'   => Env::get('cache.redis_password', ''),
            'select'     => Env::get('cache.redis_select', 0),
            'timeout'    => 0,
            'expire'     => 0,
            'persistent' => false,
            'prefix'     => Env::get('cache.redis_prefix', 'tp_'),
        ],
    ],
];
