<?php
/**
 * 公共函数库
 */

use think\facade\Env;

/**
 * 获取当前应用的URL
 */
function app_url(string $path = ''): string
{
    $host = Env::get('app.host', 'http://localhost');
    return rtrim($host, '/') . '/' . ltrim($path, '/');
}

/**
 * 获取API URL
 */
function api_url(string $path = ''): string
{
    return app_url('api/' . ltrim($path, '/'));
}

/**
 * 获取静态资源URL
 */
function static_url(string $path = ''): string
{
    return app_url('static/' . ltrim($path, '/'));
}

/**
 * 获取封面图完整URL
 */
function get_cover_url(string $cover = ''): string
{
    if (empty($cover)) {
        return '';
    }
    if (strpos($cover, 'http') === 0) {
        return $cover;
    }
    return static_url($cover);
}

/**
 * 简单汉字转拼音
 */
function pinyin(string $str): string
{
    // 简单的汉字转拼音（需要安装pinyin扩展或使用第三方库）
    // 这里返回首字母缩写
    $py = '';
    $len = mb_strlen($str, 'utf-8');
    for ($i = 0; $i < $len; $i++) {
        $char = mb_substr($str, $i, 1, 'utf-8');
        $asc = ord($char);
        if ($asc > 0 && $asc < 128) {
            $py .= $char;
        } else {
            // 简化的汉字处理（实际项目建议使用完整的拼音库）
            $py .= dechex($asc);
        }
    }
    return preg_replace('/[^a-z0-9]/', '', strtolower($py));
}

/**
 * 生成随机字符串
 */
function random_string(int $length = 16): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

/**
 * 安全过滤HTML
 */
function escape_html(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * 验证手机号
 */
function is_mobile(string $mobile): bool
{
    return preg_match('/^1[3-9]\d{9}$/', $mobile) === 1;
}

/**
 * 验证邮箱
 */
function is_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 格式化文件大小
 */
function format_size(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 4) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * 格式化播放量
 */
function format_count(int $count): string
{
    if ($count >= 100000000) {
        return round($count / 100000000, 1) . '亿';
    }
    if ($count >= 10000) {
        return round($count / 10000, 1) . '万';
    }
    if ($count >= 1000) {
        return round($count / 1000, 1) . '千';
    }
    return (string)$count;
}

/**
 * 获取客户端IP
 */
function get_client_ip(): string
{
    $ip = '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return trim($ip);
}

/**
 * 记录日志
 */
function log_info(string $message, array $context = []): void
{
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($context)) {
        $log .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    $logFile = runtime_path('logs') . date('Ymd') . '.log';
    file_put_contents($logFile, $log . PHP_EOL, FILE_APPEND);
}
