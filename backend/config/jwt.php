<?php
use think\facade\Env;

// JWT配置
return [
    'secret' => Env::get('jwt.secret', 'your-secret-key'),
    'expire' => Env::get('jwt.expire', 604800),      // 7天
    'refresh_expire' => Env::get('jwt.refresh_expire', 2592000), // 30天
];
