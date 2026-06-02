<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Admin;
use app\model\AdminLog;
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
        AdminLog::record($admin->id, AdminLog::TYPE_LOGIN, '', $this->request->ip());

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
     * 退出登录
     */
    public function logout()
    {
        $admin = $this->getCurrentAdmin();
        if ($admin) {
            AdminLog::record($admin['id'], AdminLog::TYPE_LOGOUT, '', $this->request->ip());
        }
        return $this->success(null, '已退出');
    }
}
