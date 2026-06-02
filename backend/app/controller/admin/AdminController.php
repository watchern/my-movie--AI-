<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Admin;
use app\model\AdminLoginLog;

/**
 * 管理端 - 管理员管理
 */
class AdminController extends BaseController
{
    /**
     * 管理员列表
     */
    public function list()
    {
        $data = $this->getData();
        $keyword = trim($data['keyword'] ?? '');
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(100, intval($data['limit'] ?? 20)));

        $query = new Admin();

        if (!empty($keyword)) {
            $query = $query->where('username', 'like', "%{$keyword}%");
        }

        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select();

        $total = $query->count();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'username' => $item->username,
                'nickname' => $item->nickname,
                'avatar' => $item->avatar,
                'status' => $item->status,
                'status_name' => $item->status_name,
                'last_login_time' => $item->last_login_time,
                'last_login_ip' => $item->last_login_ip,
                'created_at' => $item->created_at,
            ];
        }

        return $this->success([
            'list' => $result,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 添加管理员
     */
    public function add()
    {
        $data = $this->getData();
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');
        $nickname = trim($data['nickname'] ?? '');
        $status = intval($data['status'] ?? 1);

        if (empty($username)) {
            return $this->error('用户名不能为空');
        }

        if (strlen($username) < 3 || strlen($username) > 20) {
            return $this->error('用户名长度需在3-20个字符之间');
        }

        if (empty($password) || strlen($password) < 6) {
            return $this->error('密码长度不能少于6位');
        }

        // 检查用户名是否已存在
        $exists = Admin::where('username', $username)->find();
        if ($exists) {
            return $this->error('用户名已存在');
        }

        $admin = new Admin();
        $admin->username = $username;
        $admin->password = $password;
        $admin->nickname = $nickname ?: $username;
        $admin->status = $status;
        $admin->save();

        return $this->success(['id' => $admin->id], '添加成功');
    }

    /**
     * 更新管理员
     */
    public function update()
    {
        $data = $this->getData();
        $id = intval($data['id'] ?? 0);
        $nickname = trim($data['nickname'] ?? '');
        $password = trim($data['password'] ?? '');
        $status = intval($data['status'] ?? 1);

        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $admin = Admin::find($id);
        if (!$admin) {
            return $this->error('管理员不存在');
        }

        $currentAdmin = $this->getCurrentAdmin();

        if ($id == 1) {
            if ($status == 0) {
                return $this->error('不能禁用第一个管理员');
            }
        }

        if ($currentAdmin && $id == $currentAdmin['id']) {
            if ($status == 0) {
                return $this->error('不能禁用自己');
            }
        }

        if (!empty($nickname)) {
            $admin->nickname = $nickname;
        }

        if (!empty($password)) {
            if (strlen($password) < 6) {
                return $this->error('密码长度不能少于6位');
            }
            $admin->password = $password;
        }

        $admin->status = $status;
        $admin->save();

        return $this->success(null, '更新成功');
    }

    /**
     * 删除管理员
     */
    public function delete()
    {
        $data = $this->getData();
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return $this->error('请选择要删除的管理员');
        }

        // 不允许删除自己
        $currentAdmin = $this->getCurrentAdmin();
        foreach ($ids as $id) {
            if ($id == $currentAdmin['id']) {
                return $this->error('不能删除当前登录的管理员');
            }
            if ($id == 1) {
                return $this->error('不能删除第一个管理员');
            }
        }

        Admin::whereIn('id', $ids)->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 管理员登录日志
     */
    public function loginLogs()
    {
        $data = $this->getData();
        $keyword = trim($data['keyword'] ?? '');
        $device = trim($data['device'] ?? '');
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(100, intval($data['limit'] ?? 20)));

        $query = AdminLoginLog::with(['admin']);

        if (!empty($keyword)) {
            $query->whereHas('admin', function($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->whereOr('nickname', 'like', "%{$keyword}%");
            });
        }
        if (!empty($device)) {
            $query->where('device', $device);
        }

        $total = $query->count();

        $list = $query->order('login_at', 'desc')
            ->page($page, $limit)
            ->select();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'admin_id' => $item->admin_id,
                'username' => $item->admin ? $item->admin->username : '未知',
                'nickname' => $item->admin ? $item->admin->nickname : '',
                'login_ip' => $item->login_ip,
                'device' => $item->device,
                'device_name' => $item->device_name,
                'device_info' => $item->device_info,
                'login_at' => $item->login_at,
            ];
        }

        return $this->success([
            'list' => $result,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }
}
