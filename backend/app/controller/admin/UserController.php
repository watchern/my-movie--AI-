<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\User;
use app\model\CardKey;
use app\model\CardRechargeLog;
use app\model\WatchHistory;
use app\model\Favorite;

/**
 * 管理端 - 用户管理
 */
class AdminUserController extends BaseController
{
    /**
     * 用户列表
     */
    public function list()
    {
        $data = $this->getData();
        $keyword = trim($data['keyword'] ?? '');
        $vipStatus = isset($data['vip_status']) ? intval($data['vip_status']) : -1;
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(100, intval($data['limit'] ?? 20)));

        $where = [];
        if (!empty($keyword)) {
            $where[] = ['email', 'like', "%{$keyword}%"];
        }
        if ($vipStatus >= 0) {
            $where[] = ['vip_status', '=', $vipStatus];
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
                'total_watch_time' => $item->total_watch_time,
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
                'total_watch_time' => $user->total_watch_time,
                'created_at' => $user->created_at,
            ],
            'favorite_count' => $favoriteCount,
            'history_count' => $historyCount,
        ]);
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

        return $this->success(null, '更新成功');
    }

    /**
     * 卡密列表
     */
    public function cardList()
    {
        $data = $this->getData();
        $status = isset($data['status']) ? intval($data['status']) : -1;
        $type = isset($data['type']) ? intval($data['type']) : 0;
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(100, intval($data['limit'] ?? 20)));

        $where = [];
        if ($status >= 0) {
            $where[] = ['status', '=', $status];
        }
        if ($type > 0) {
            $where[] = ['type', '=', $type];
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
                'card_no' => $item->card_no,
                'card_pwd' => $item->card_pwd,
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
        $type = intval($data['type'] ?? 3);
        $count = intval($data['count'] ?? 1);
        $price = floatval($data['price'] ?? 0);

        if ($count < 1 || $count > 100) {
            return $this->error('生成数量需在1-100之间');
        }

        $cards = [];
        for ($i = 0; $i < $count; $i++) {
            $card = new CardKey();
            $card->card_no = $this->generateCardNo();
            $card->card_pwd = $this->generateCardPwd();
            $card->type = $type;
            $card->days = CardKey::TYPE_DAYS[$type] ?? 30;
            $card->price = $price;
            $card->status = CardKey::STATUS_UNUSED;
            $card->expired_at = date('Y-m-d H:i:s', strtotime('+1 year'));
            $card->save();

            $cards[] = [
                'card_no' => $card->card_no,
                'card_pwd' => $card->card_pwd,
                'type_name' => $card->type_name,
                'days' => $card->days,
            ];
        }

        return $this->success($cards, '生成成功');
    }

    /**
     * 删除卡密
     */
    public function deleteCard()
    {
        $data = $this->getData();
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return $this->error('请选择要删除的卡密');
        }

        // 只删除未使用的
        CardKey::whereIn('id', $ids)
            ->where('status', CardKey::STATUS_UNUSED)
            ->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 生成卡号
     */
    private function generateCardNo(): string
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)) . '-' .
               strtoupper(substr(md5(uniqid(mt_rand(), true)), 8, 8));
    }

    /**
     * 生成卡密
     */
    private function generateCardPwd(): string
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }
}
