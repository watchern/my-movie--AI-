<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Admin;
use app\model\AdminLog;

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

        // 记录操作日志
        $currentAdmin = $this->getCurrentAdmin();
        if ($currentAdmin) {
            AdminLog::record($currentAdmin['id'], AdminLog::TYPE_ADD_ADMIN, "添加管理员: {$username}", $this->request->ip());
        }

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

        // 记录操作日志（在更新前获取旧状态）
        $oldStatus = $admin->status;
        $admin->status = $status;
        $admin->save();

        // 记录操作日志
        $currentAdmin = $this->getCurrentAdmin();
        if ($currentAdmin) {
            if (!empty($nickname)) {
                AdminLog::record($currentAdmin['id'], AdminLog::TYPE_EDIT_ADMIN, "编辑管理员: {$admin->username}, 昵称: {$nickname}", $this->request->ip());
            }
            if (!empty($password)) {
                AdminLog::record($currentAdmin['id'], AdminLog::TYPE_CHANGE_PASSWORD, "修改管理员密码: {$admin->username}", $this->request->ip());
            }
            if ($oldStatus != $status) {
                $type = $status == 1 ? AdminLog::TYPE_ENABLE_ADMIN : AdminLog::TYPE_DISABLE_ADMIN;
                $typeName = $status == 1 ? '启用' : '禁用';
                AdminLog::record($currentAdmin['id'], $type, "{$typeName}管理员: {$admin->username}", $this->request->ip());
            }
        }

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
        if (!$currentAdmin) {
            return $this->error('管理员未登录');
        }
        foreach ($ids as $id) {
            if ($id == $currentAdmin['id']) {
                return $this->error('不能删除当前登录的管理员');
            }
            if ($id == 1) {
                return $this->error('不能删除第一个管理员');
            }
        }

        // 记录删除前的管理员信息
        $admins = Admin::whereIn('id', $ids)->select();
        $deletedUsernames = [];
        foreach ($admins as $admin) {
            $deletedUsernames[] = $admin->username;
        }

        Admin::whereIn('id', $ids)->delete();

        // 记录操作日志
        if ($currentAdmin && !empty($deletedUsernames)) {
            AdminLog::record($currentAdmin['id'], AdminLog::TYPE_DELETE_ADMIN, "删除管理员: " . implode(', ', $deletedUsernames), $this->request->ip());
        }

        return $this->success(null, '删除成功');
    }

    /**
     * 管理员操作日志
     */
    public function logs()
    {
        $data = $this->getData();
        $keyword = trim($data['keyword'] ?? '');
        $type = trim($data['type'] ?? '');
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(100, intval($data['limit'] ?? 20)));

        // 先查询日志表
        $query = new AdminLog();

        // 如果有关键词搜索，先找到匹配的管理员ID
        $adminIds = [];
        if (!empty($keyword)) {
            $admins = Admin::where('username', 'like', "%{$keyword}%")
                ->whereOr('nickname', 'like', "%{$keyword}%")
                ->select();
            foreach ($admins as $admin) {
                $adminIds[] = $admin->id;
            }
            // 如果没有找到匹配的管理员ID，就添加一个不存在的ID，确保查询结果为空
            if (empty($adminIds)) {
                $adminIds[] = -1;
            }
            $query = $query->whereIn('admin_id', $adminIds);
        }

        if (!empty($type)) {
            $query = $query->where('type', $type);
        }

        $total = $query->count();

        $list = $query->order('created_at', 'desc')
            ->page($page, $limit)
            ->select();

        // 收集所有管理员ID
        $adminIdsForQuery = [];
        foreach ($list as $item) {
            if ($item->admin_id > 0 && !in_array($item->admin_id, $adminIdsForQuery)) {
                $adminIdsForQuery[] = $item->admin_id;
            }
        }

        // 批量查询管理员信息
        $adminMap = [];
        if (!empty($adminIdsForQuery)) {
            $admins = Admin::whereIn('id', $adminIdsForQuery)->select();
            foreach ($admins as $admin) {
                $adminMap[$admin->id] = $admin;
            }
        }

        // 定义类型映射
        $typeMap = [
            \app\model\AdminLog::TYPE_LOGIN => '登录',
            \app\model\AdminLog::TYPE_LOGOUT => '登出',
            \app\model\AdminLog::TYPE_ADD_ADMIN => '添加管理员',
            \app\model\AdminLog::TYPE_EDIT_ADMIN => '编辑管理员',
            \app\model\AdminLog::TYPE_DELETE_ADMIN => '删除管理员',
            \app\model\AdminLog::TYPE_DISABLE_ADMIN => '禁用管理员',
            \app\model\AdminLog::TYPE_ENABLE_ADMIN => '启用管理员',
            \app\model\AdminLog::TYPE_CHANGE_PASSWORD => '修改密码',
            \app\model\AdminLog::TYPE_ADD_VIDEO => '添加视频',
            \app\model\AdminLog::TYPE_EDIT_VIDEO => '编辑视频',
            \app\model\AdminLog::TYPE_DELETE_VIDEO => '删除视频',
            \app\model\AdminLog::TYPE_ADD_CATEGORY => '添加分类',
            \app\model\AdminLog::TYPE_EDIT_CATEGORY => '编辑分类',
            \app\model\AdminLog::TYPE_DELETE_CATEGORY => '删除分类',
            \app\model\AdminLog::TYPE_ADD_VIP_CARD => '生成兑换码',
            \app\model\AdminLog::TYPE_DELETE_VIP_CARD => '删除兑换码',
            \app\model\AdminLog::TYPE_DISABLE_VIP_CARD => '禁用兑换码',
            \app\model\AdminLog::TYPE_EDIT_CONFIG => '修改配置',
            \app\model\AdminLog::TYPE_OTHER => '其他操作',
        ];
        
        $result = [];
        foreach ($list as $item) {
            $admin = isset($adminMap[$item->admin_id]) ? $adminMap[$item->admin_id] : null;
            $type = is_object($item->type) ? (string)$item->type : $item->type;
            $result[] = [
                'id' => $item->id,
                'admin_id' => $item->admin_id,
                'username' => $admin ? $admin->username : '未知',
                'nickname' => $admin ? $admin->nickname : '',
                'type' => $item->type,
                'type_name' => $typeMap[$type] ?? '未知操作',
                'detail' => $item->detail,
                'ip' => $item->ip,
                'device_info' => $item->device_info,
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
}
