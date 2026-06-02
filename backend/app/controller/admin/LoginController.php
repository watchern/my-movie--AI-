<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Admin;
use app\model\AdminLoginLog;
use app\common\JwtHelper;

/**
 * 管理端登录控制器
 */
class LoginController extends BaseController
{
    /**
     * 管理员登录
     */
    public function login()
    {
        $data = $this->getData();
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->error('用户名和密码不能为空');
        }

        // 查找管理员
        $admin = Admin::where('username', $username)->find();

        if (!$admin) {
            return $this->error('用户名或密码错误');
        }

        if (!password_verify($password, $admin->password)) {
            return $this->error('用户名或密码错误');
        }

        if ($admin->status != 1) {
            return $this->error('账号已被禁用');
        }

        // 记录登录日志
        $this->recordLoginLog($admin->id);

        // 更新登录信息
        $admin->last_login_time = date('Y-m-d H:i:s');
        $admin->last_login_ip = $this->request->ip();
        $admin->save();

        // 生成Token
        $token = JwtHelper::generateToken($admin->id, ['type' => 'admin']);

        return $this->success([
            'id' => $admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'avatar' => $admin->avatar,
            'token' => $token,
        ], '登录成功');
    }

    /**
     * 记录管理员登录日志
     */
    private function recordLoginLog(int $adminId)
    {
        $log = new AdminLoginLog();
        $log->admin_id = $adminId;
        $log->login_ip = $this->request->ip();
        $log->device = $this->getDevice();
        $log->device_info = $this->request->header('User-Agent') ?? '';
        $log->login_at = date('Y-m-d H:i:s');
        $log->save();
    }

    /**
     * 获取设备类型
     */
    private function getDevice(): string
    {
        $userAgent = $this->request->header('User-Agent') ?? '';
        if (preg_match('/mobile/i', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        } elseif (preg_match('/windows|mac|linux/i', $userAgent)) {
            return 'desktop';
        }
        return 'other';
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        return $this->success(null, '已退出');
    }
}
