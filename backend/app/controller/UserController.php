<?php
namespace app\controller;

use app\BaseController;
use app\model\User;

/**
 * 用户控制器
 */
class UserController extends BaseController
{
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
            'username' => $user->username,
            'avatar' => $user->avatar,
            'phone' => $user->phone,
            'vip_status' => $user->vip_status,
            'vip_expire_time' => $user->vip_expire_time,
            'vip_remain_days' => $user->getVipRemainDays(),
            'total_watch_time' => $user->total_watch_time,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * 更新用户信息
     */
    public function update()
    {
        $userId = $this->request->uid ?? 0;
        $data = $this->getData();

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在');
        }

        // 更新头像
        if (isset($data['avatar'])) {
            $user->avatar = trim($data['avatar']);
        }

        // 更新手机号
        if (isset($data['phone'])) {
            $phone = trim($data['phone']);
            // 检查手机号是否被占用
            if (User::where('phone', $phone)->where('id', '<>', $userId)->find()) {
                return $this->error('手机号已被使用');
            }
            $user->phone = $phone;
        }

        $user->save();

        return $this->success([
            'user_id' => $user->id,
            'username' => $user->username,
            'avatar' => $user->avatar,
            'phone' => $user->phone,
        ], '更新成功');
    }
}
