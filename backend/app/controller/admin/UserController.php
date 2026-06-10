<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\User;
use app\model\CardKey;
use app\model\WatchHistory;
use app\model\Favorite;
use app\model\LoginLog;
use app\model\VipTransaction;
use app\model\AdminLog;

/**
 * 管理端 - 用户管理
 */
class UserController extends BaseController
{
    /**
     * 用户列表
     */
    public function list()
    {
        $data = $this->getData();
        $keyword = trim($data['keyword'] ?? '');
        $vipStatus = -1;
        if (isset($data['vip_status']) && $data['vip_status'] !== '' && $data['vip_status'] !== null) {
            $vipStatus = intval($data['vip_status']);
        }
        $vipDaysOperator = $data['vip_days_operator'] ?? '';
        $vipDaysValue = isset($data['vip_days_value']) ? intval($data['vip_days_value']) : -1;
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(100, intval($data['limit'] ?? 20)));

        $where = [];
        if (!empty($keyword)) {
            $where[] = ['email', 'like', "%{$keyword}%"];
        }
        if ($vipStatus >= 0) {
            $where[] = ['vip_status', '=', $vipStatus];
        }

        if (!empty($vipDaysOperator) && $vipDaysValue >= 0 && in_array($vipDaysOperator, ['>', '=', '<'])) {
            $now = time();
            $allList = User::where($where)
                ->order('id', 'desc')
                ->select()
                ->toArray();

            foreach ($allList as &$item) {
                $expireTime = !empty($item['vip_expire_time']) ? strtotime($item['vip_expire_time']) : 0;
                if ($item['vip_status'] && $expireTime > 0) {
                    $diff = $expireTime - $now;
                    if ($diff <= 0) {
                        $item['vip_remain_days'] = 0;
                    } else {
                        $item['vip_remain_days'] = floor($diff / 86400);
                    }
                } else {
                    $item['vip_remain_days'] = 0;
                }
            }

            $filteredList = array_filter($allList, function($item) use ($vipDaysOperator, $vipDaysValue) {
                $remainDays = $item['vip_remain_days'] ?? 0;
                switch ($vipDaysOperator) {
                    case '>':
                        return $remainDays > $vipDaysValue;
                    case '=':
                        return $remainDays == $vipDaysValue;
                    case '<':
                        return $remainDays < $vipDaysValue;
                    default:
                        return true;
                }
            });

            $total = count($filteredList);
            $filteredList = array_values($filteredList);
            $list = array_slice($filteredList, ($page - 1) * $limit, $limit);

            $result = [];
            foreach ($list as $item) {
                $user = User::find($item['id']);
                $result[] = [
                    'id' => $item['id'],
                    'email' => $item['email'],
                    'phone' => $item['phone'],
                    'vip_status' => $item['vip_status'],
                    'vip_expire_time' => $item['vip_expire_time'],
                    'vip_remain_days' => $item['vip_remain_days'],
                    'vip_remain_time' => $user ? $user->getVipRemainTime() : '0天0小时0分钟',
                    'total_watch_time' => $item['total_watch_time'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ];
            }

            return $this->success([
                'list' => $result,
                'total' => $total,
            ]);
        }

        $list = User::where($where)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();

        $total = User::where($where)->count();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'email' => $item->email,
                'phone' => $item->phone,
                'vip_status' => $item->vip_status,
                'vip_expire_time' => $item->vip_expire_time,
                'vip_remain_days' => $item->getVipRemainDays(),
                'vip_remain_time' => $item->getVipRemainTime(),
                'total_watch_time' => $item->total_watch_time,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
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
     * 用户详情
     */
    public function detail()
    {
        $id = intval($this->request->param('id', 0));

        $user = User::with(['watchHistory' => function ($query) {
            $query->order('watched_at', 'desc')->limit(10);
        }])->find($id);

        if (!$user) {
            return $this->error('用户不存在');
        }

        // 获取收藏数
        $favoriteCount = Favorite::where('user_id', $id)->count();

        // 获取历史记录数
        $historyCount = WatchHistory::where('user_id', $id)->count();

        return $this->success([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'vip_status' => $user->vip_status,
                'vip_expire_time' => $user->vip_expire_time,
                'vip_remain_days' => $user->getVipRemainDays(),
                'vip_remain_time' => $user->getVipRemainTime(),
                'total_watch_time' => $user->total_watch_time,
                'created_at' => $user->created_at,
            ],
            'favorite_count' => $favoriteCount,
            'history_count' => $historyCount,
        ]);
    }

    /**
     * 新增用户
     */
    public function addUser()
    {
        $input = file_get_contents('php://input');
        $json = json_decode($input, true) ?: [];
        $email = trim($json['email'] ?? '');

        if (empty($email)) {
            return $this->error('邮箱不能为空');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('邮箱格式不正确');
        }

        $exists = User::where('email', $email)->find();
        if ($exists) {
            return $this->error('邮箱已存在');
        }

        $user = new User();
        $user->email = $email;
        $user->password = '123456';
        $user->vip_status = 0;
        $user->save();

        return $this->success(['id' => $user->id, 'default_password' => '123456'], '添加成功');
    }

    /**
     * 修改用户VIP状态
     */
    public function updateVip()
    {
        $data = $this->getData();
        $userId = intval($data['user_id'] ?? 0);
        $vipStatus = intval($data['vip_status'] ?? 0);
        $days = intval($data['days'] ?? 0);

        if ($userId <= 0) {
            return $this->error('参数错误');
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $oldStatus = $user->vip_status;
        $oldExpireTime = $user->vip_expire_time;

        $user->vip_status = $vipStatus;

        if ($vipStatus == 1) {
            if ($days > 0) {
                $user->vip_expire_time = date('Y-m-d H:i:s', strtotime("+{$days} days"));
            } else {
                $user->vip_expire_time = null; // 永久
            }
        } else {
            $user->vip_expire_time = null;
        }

        $user->save();

        // 添加VIP调整记录
        $description = "管理员调整VIP: ";
        if ($vipStatus == 1) {
            if ($days > 0) {
                $description .= "开通VIP，延长{$days}天";
            } else {
                $description .= "开通永久VIP";
            }
        } else {
            $description .= "取消VIP";
        }
        $this->addAdminLog($userId, VipTransaction::TYPE_ADMIN, $description);

        return $this->success(null, '更新成功');
    }

    /**
     * 重置用户密码
     */
    public function resetPassword()
    {
        $data = $this->getData();
        $userId = intval($data['user_id'] ?? 0);

        if ($userId <= 0) {
            return $this->error('参数错误');
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $newPassword = '123456';
        $user->password = $newPassword;
        $user->save();

        // 添加管理员操作日志
        $this->addAdminLog($userId, AdminLog::TYPE_OTHER, "重置用户密码");

        return $this->success(['password' => $newPassword], '密码已重置为: 123456');
    }

    /**
     * 卡密列表
     */
    public function cardList()
    {
        $data = $this->getData();
        $code = trim($data['code'] ?? '');
        $status = -1;
        if (isset($data['status']) && $data['status'] !== '' && $data['status'] !== null) {
            $status = intval($data['status']);
        }
        $type = 0;
        if (isset($data['type']) && $data['type'] !== '' && $data['type'] !== null) {
            $type = intval($data['type']);
        }
        $usedUserId = trim($data['used_user_id'] ?? '');
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(100, intval($data['limit'] ?? 20)));

        $where = [];
        if (!empty($code)) {
            $where[] = ['code', 'like', "%{$code}%"];
        }
        if ($status >= 0) {
            $where[] = ['status', '=', $status];
        }
        if ($type > 0) {
            $where[] = ['type', '=', $type];
        }
        if (!empty($usedUserId)) {
            $where[] = ['used_user_id', 'like', "%{$usedUserId}%"];
        }

        $list = CardKey::where($where)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();

        $total = CardKey::where($where)->count();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'code' => $item->code,
                'type' => $item->type,
                'type_name' => $item->type_name,
                'days' => $item->days,
                'price' => $item->price,
                'status' => $item->status,
                'used_user_id' => $item->used_user_id,
                'used_at' => $item->used_at,
                'created_at' => $item->created_at,
                'expired_at' => $item->expired_at,
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
     * 生成卡密
     */
    public function generateCard()
    {
        $data = $this->getData();
        $count = intval($data['count'] ?? 1);
        $days = intval($data['days'] ?? 30);
        $price = floatval($data['price'] ?? 0);

        if ($count < 1 || $count > 100) {
            return $this->error('生成数量需在1-100之间');
        }

        if ($days < 1) {
            return $this->error('天数必须大于0');
        }

        $type = $this->getTypeByDays($days);

        $cards = [];
        for ($i = 0; $i < $count; $i++) {
            $card = new CardKey();
            $card->code = $this->generateCode();
            $card->type = $type;
            $card->days = $days;
            $card->price = $price;
            $card->status = CardKey::STATUS_UNUSED;
            $card->expired_at = date('Y-m-d H:i:s', strtotime('+1 year'));
            $card->save();

            $cards[] = [
                'code' => $card->code,
                'type_name' => $card->type_name,
                'days' => $card->days,
            ];
        }

        // 生成兑换码记录已移到管理员操作日志，这里不再写入VIP变动记录
        $typeNameMap = [
            CardKey::TYPE_DAY => '天卡',
            CardKey::TYPE_WEEK => '周卡',
            CardKey::TYPE_MONTH => '月卡',
            CardKey::TYPE_QUARTER => '季卡',
            CardKey::TYPE_YEAR => '年卡',
            CardKey::TYPE_FOREVER => '永久卡',
        ];
        $typeName = $typeNameMap[$type] ?? '未知';
        $description = "生成兑换码: {$typeName}x{$count}, 共{$count}个";
        
        // 记录到管理员操作日志
        $currentAdmin = $this->getCurrentAdmin();
        if ($currentAdmin) {
            AdminLog::record($currentAdmin['id'], AdminLog::TYPE_ADD_VIP_CARD, $description, $this->request->ip());
        }

        return $this->success($cards, '生成成功');
    }

    private function generateCode(): string
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)) . '-' .
               strtoupper(substr(md5(uniqid(mt_rand(), true)), 8, 8));
    }

    private function getTypeByDays(int $days): int
    {
        $typeDaysMap = [
            1 => CardKey::TYPE_DAY,
            7 => CardKey::TYPE_WEEK,
            30 => CardKey::TYPE_MONTH,
            90 => CardKey::TYPE_QUARTER,
            365 => CardKey::TYPE_YEAR,
        ];

        if (isset($typeDaysMap[$days])) {
            return $typeDaysMap[$days];
        }

        if ($days >= 36500) {
            return CardKey::TYPE_FOREVER;
        }

        return CardKey::TYPE_DAY;
    }

    /**
     * 删除兑换码
     */
    public function deleteCard()
    {
        $data = $this->getData();
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return $this->error('请选择要删除的兑换码');
        }

        // 获取要删除的兑换码信息（未使用和已过期的）
        $cards = CardKey::whereIn('id', $ids)
            ->whereIn('status', [CardKey::STATUS_UNUSED, CardKey::STATUS_EXPIRED])
            ->select();

        if ($cards->isEmpty()) {
            return $this->error('没有可删除的兑换码');
        }

        $cardCodes = $cards->pluck('code')->toArray();
        $description = '删除兑换码: ' . implode(', ', $cardCodes);

        // 记录到管理员操作日志
        $currentAdmin = $this->getCurrentAdmin();
        if ($currentAdmin) {
            AdminLog::record($currentAdmin['id'], AdminLog::TYPE_DELETE_VIP_CARD, $description, $this->request->ip());
        }

        // 删除兑换码（未使用和已过期的）
        CardKey::whereIn('id', $ids)
            ->whereIn('status', [CardKey::STATUS_UNUSED, CardKey::STATUS_EXPIRED])
            ->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 设置兑换码失效
     */
    public function disableCard()
    {
        $data = $this->getData();
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return $this->error('请选择要失效的兑换码');
        }

        // 获取要失效的兑换码信息用于记录
        $cards = CardKey::whereIn('id', $ids)
            ->where('status', CardKey::STATUS_UNUSED)
            ->select();

        if ($cards->isEmpty()) {
            return $this->error('没有可失效的兑换码');
        }

        $cardCodes = $cards->pluck('code')->toArray();
        $description = '设置兑换码失效: ' . implode(', ', $cardCodes);

        // 记录到管理员操作日志
        $currentAdmin = $this->getCurrentAdmin();
        if ($currentAdmin) {
            AdminLog::record($currentAdmin['id'], AdminLog::TYPE_DISABLE_VIP_CARD, $description, $this->request->ip());
        }

        // 设置兑换码失效
        CardKey::whereIn('id', $ids)
            ->where('status', CardKey::STATUS_UNUSED)
            ->update([
                'status' => CardKey::STATUS_EXPIRED,
                'expired_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->success(null, '设置成功');
    }

    /**
     * 添加管理员操作日志
     */
    private function addAdminLog(int $userId, string $type, string $description)
    {
        $transaction = new VipTransaction();
        $transaction->user_id = $userId;
        $transaction->type = $type;
        $transaction->days = 0;
        $transaction->description = $description;
        $transaction->save();
    }

    /**
     * 登录日志列表
     */
    public function loginLogs()
    {
        $data = $this->getData();
        $keyword = trim($data['keyword'] ?? '');
        $device = trim($data['device'] ?? '');
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(100, intval($data['limit'] ?? 20)));

        $query = LoginLog::with(['user']);

        if (!empty($keyword)) {
            $query->whereHas('user', function($q) use ($keyword) {
                $q->where('email', 'like', "%{$keyword}%");
            });
        }
        if (!empty($device)) {
            $query->where('device', $device);
        }

        $list = $query->order('login_at', 'desc')
            ->page($page, $limit)
            ->select();

        $total = $query->count();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'email' => $item->user ? $item->user->email : '用户已删除',
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

    /**
     * 观看历史列表
     */
    public function watchHistory()
    {
        $data = $this->getData();
        $keyword = trim($data['keyword'] ?? '');
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(100, intval($data['limit'] ?? 20)));

        $query = WatchHistory::with(['user', 'video']);

        if (!empty($keyword)) {
            $query->whereHas('user', function($q) use ($keyword) {
                $q->where('email', 'like', "%{$keyword}%");
            })->whereOr('id', 'in', function($q) use ($keyword) {
                $q->table('videos')->where('title', 'like', "%{$keyword}%")->field('id');
            });
        }

        $list = $query->order('watched_at', 'desc')
            ->page($page, $limit)
            ->select();

        $total = $query->count();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'email' => $item->user ? $item->user->email : '用户已删除',
                'video_id' => $item->video_id,
                'video_title' => $item->video ? $item->video->title : '视频已删除',
                'progress' => $item->progress_text,
                'last_position' => $item->last_position,
                'duration' => $item->duration,
                'watched_at' => $item->watched_at,
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
     * 收藏列表
     */
    public function favorites()
    {
        $data = $this->getData();
        $keyword = trim($data['keyword'] ?? '');
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(100, intval($data['limit'] ?? 20)));

        $query = Favorite::with(['user', 'video']);

        if (!empty($keyword)) {
            $query->whereHas('user', function($q) use ($keyword) {
                $q->where('email', 'like', "%{$keyword}%");
            });
        }

        $list = $query->order('created_at', 'desc')
            ->page($page, $limit)
            ->select();

        $total = $query->count();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'email' => $item->user ? $item->user->email : '用户已删除',
                'video_id' => $item->video_id,
                'video_title' => $item->video ? $item->video->title : '视频已删除',
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
