<?php
namespace app\common;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Config;

/**
 * JWT工具类
 */
class JwtHelper
{
    private static string $secret;
    private static int $expire;
    private static int $refreshExpire;

    /**
     * 初始化
     */
    private static function init()
    {
        self::$secret = Config::get('jwt.secret', 'default-secret');
        self::$expire = Config::get('jwt.expire', 604800);
        self::$refreshExpire = Config::get('jwt.refresh_expire', 2592000);
    }

    /**
     * 生成Token
     */
    public static function generateToken(int $userId, array $extra = []): string
    {
        self::init();

        $time = time();
        $payload = [
            'iss' => 'moive-app',
            'aud' => 'moive-app-api',
            'iat' => $time,
            'nbf' => $time,
            'exp' => $time + self::$expire,
            'user_id' => $userId,
            'extra' => $extra,
        ];

        return JWT::encode($payload, self::$secret, 'HS256');
    }

    /**
     * 生成刷新Token
     */
    public static function generateRefreshToken(int $userId): string
    {
        self::init();

        $time = time();
        $payload = [
            'iss' => 'moive-app',
            'aud' => 'moive-app-api',
            'iat' => $time,
            'nbf' => $time,
            'exp' => $time + self::$refreshExpire,
            'user_id' => $userId,
            'type' => 'refresh',
        ];

        return JWT::encode($payload, self::$secret, 'HS256');
    }

    /**
     * 验证Token
     */
    public static function verify(string $token): ?object
    {
        self::init();

        try {
            return JWT::decode($token, new Key(self::$secret, 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 从Token获取用户ID
     */
    public static function getUserId(string $token): ?int
    {
        $payload = self::verify($token);
        return $payload ? ($payload->user_id ?? null) : null;
    }

    /**
     * 解析Token获取payload
     */
    public static function getPayload(string $token): ?array
    {
        $payload = self::verify($token);
        return $payload ? (array)$payload : null;
    }
}
