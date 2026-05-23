<?php
namespace app\middleware;

use app\common\JwtHelper;

/**
 * API认证中间件
 */
class ApiAuth
{
    public function handle($request, \Closure $next)
    {
        $token = $request->header('Authorization', '');

        // 移除Bearer前缀
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            return json([
                'code' => 401,
                'msg' => '未登录或Token已过期',
                'data' => null,
            ], 401);
        }

        $payload = JwtHelper::verify($token);

        if (!$payload) {
            return json([
                'code' => 401,
                'msg' => 'Token无效',
                'data' => null,
            ], 401);
        }

        // 检查是否是刷新Token
        if (isset($payload->type) && $payload->type === 'refresh') {
            return json([
                'code' => 401,
                'msg' => '无效的访问Token',
                'data' => null,
            ], 401);
        }

        // 将用户信息注入到请求中
        $request->uid = $payload->user_id ?? 0;

        return $next($request);
    }
}
