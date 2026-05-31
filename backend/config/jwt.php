<?php
use think\facade\Env;

// JWT配置
return [
    'secret' => Env::get('JWT_SECRET', 'your-secret-key'),
    'expire' => Env::get('JWT_EXPIRE', 604800),      // 7天
    'refresh_expire' => Env::get('JWT_REFRESH_EXPIRE', 2592000), // 30天
];
