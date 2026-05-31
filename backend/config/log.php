<?php
use think\facade\Env;

// 日志配置
return [
    // 默认日志通道
    'default' => Env::get('log.channel', 'file'),

    // 日志通道
    'channels' => [
        'file' => [
            'type' => 'File',
            'path' => '',
            'level' => [],
            'file_size' => 2097152,
            'time_format' => 'Y-m-d H:i:s',
            'single' => false,
            'json' => false,
            'reconnect' => false,
        ],
    ],
];
