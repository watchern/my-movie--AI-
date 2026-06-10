<?php
namespace app\controller;

use app\BaseController;
use app\model\User;
use app\model\LoginLog;
use app\model\VipTransaction;
use app\common\JwtHelper;

/**
 * 用户认证控制器
 */
class AuthController extends BaseController
{
    /**
     * 用户注册
     */
    public function register()
    {
        $data = $this->getData();
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $phone = trim($data['phone'] ?? '');

        // 参数验证
        if (empty($email) || empty($password)) {
            return $this->error('邮箱和密码不能为空');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('邮箱格式不正确');
        }

        if (strlen($password) < 6) {
            return $this->error('密码长度不能少于6位');
        }

        // 检查邮箱是否已存在
        if (User::where('email', $email)->find()) {
            return $this->error('邮箱已存在');
        }

        // 创建用户
        $user = new User();
        $user->email = $email;
        $user->password = $password;
        $user->phone = $phone;
        $user->vip_status = User::VIP_NORMAL;

        if (!$user->save()) {
            return $this->error('注册失败');
        }

        // 记录登录日志
        $ip = $this->request->ip() ?? '';
        $userAgent = $this->request->header('user-agent') ?? '';
        $user->recordLogin($ip, $userAgent);

        // 生成Token
        $token = JwtHelper::generateToken($user->id);
        $refreshToken = JwtHelper::generateRefreshToken($user->id);

        return $this->success([
            'user_id' => $user->id,
            'email' => $user->email,
            'vip_status' => $user->vip_status,
            'token' => $token,
            'refresh_token' => $refreshToken,
        ], '注册成功');
    }

    /**
     * 用户登录
     */
    public function login()
    {
        $data = $this->getData();
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->error('邮箱和密码不能为空');
        }

        // 查找用户
        $user = User::where('email', $email)->find();

        if (!$user) {
            return $this->error('邮箱或密码错误');
        }

        // 调试：打印数据库中的密码哈希
        error_log("AuthController::login - DB password hash: " . $user->password);
        error_log("AuthController::login - User input password: " . $password);
        error_log("AuthController::login - password_verify result: " . (password_verify($password, $user->password) ? 'true' : 'false'));

        // 验证密码
        if (!password_verify($password, $user->password)) {
            return $this->error('邮箱或密码错误');
        }

        // 检查VIP是否过期
        if ($user->vip_status == User::VIP_ACTIVE && !empty($user->vip_expire_time)) {
            if (strtotime($user->vip_expire_time) < time()) {
                $user->vip_status = User::VIP_NORMAL;
                $user->save();
            }
        }

        // 记录登录日志
        $ip = $this->request->ip() ?? '';
        $userAgent = $this->request->header('user-agent') ?? '';
        $user->recordLogin($ip, $userAgent);

        // 生成Token
        $token = JwtHelper::generateToken($user->id);
        $refreshToken = JwtHelper::generateRefreshToken($user->id);

        return $this->success([
            'user_id' => $user->id,
            'email' => $user->email,
            'phone' => $user->phone,
            'vip_status' => $user->vip_status,
            'vip_expire_time' => $user->vip_expire_time,
            'vip_remain_days' => $user->getVipRemainDays(),
            'token' => $token,
            'refresh_token' => $refreshToken,
        ], '登录成功');
    }

    /**
     * 获取用户信息
     */
    public function info()
    {
        $userId = $this->request->uid ?? 0;

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在');
        }

        return $this->success([
            'user_id' => $user->id,
            'email' => $user->email,
            'phone' => $user->phone,
            'vip_status' => $user->vip_status,
            'vip_expire_time' => $user->vip_expire_time,
            'vip_remain_days' => $user->getVipRemainDays(),
            'total_watch_time' => $user->total_watch_time,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * 刷新Token
     */
    public function refresh()
    {
        $data = $this->getData();
        $refreshToken = $data['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            return $this->error('refresh_token不能为空');
        }

        $payload = JwtHelper::verify($refreshToken);
        if (!$payload || !isset($payload->type) || $payload->type !== 'refresh') {
            return $this->error('refresh_token无效');
        }

        // 生成新的Token
        $userId = $payload->user_id ?? 0;
        $token = JwtHelper::generateToken($userId);
        $newRefreshToken = JwtHelper::generateRefreshToken($userId);

        return $this->success([
            'token' => $token,
            'refresh_token' => $newRefreshToken,
        ]);
    }

    /**
     * 修改密码
     */
    public function changePassword()
    {
        $userId = $this->request->uid ?? 0;
        $data = $this->getData();

        $oldPassword = $data['old_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';

        if (empty($oldPassword) || empty($newPassword)) {
            return $this->error('旧密码和新密码不能为空');
        }

        if (strlen($newPassword) < 6) {
            return $this->error('新密码长度不能少于6位');
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在');
        }

        if (!password_verify($oldPassword, $user->password)) {
            return $this->error('旧密码错误');
        }

        $user->password = $newPassword;
        $user->save();

        return $this->success(null, '密码修改成功');
    }
}
