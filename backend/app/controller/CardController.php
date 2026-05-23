<?php
namespace app\controller;

use app\BaseController;
use app\model\CardKey;
use app\model\User;
use app\model\VipTransaction;

/**
 * 卡密控制器
 */
class CardController extends BaseController
{
    /**
     * 兑换卡密
     */
    public function redeem()
    {
        $userId = $this->request->uid ?? 0;
        $data = $this->getData();

        $cardNo = trim($data['card_no'] ?? '');
        $cardPwd = trim($data['card_pwd'] ?? '');

        if (empty($cardNo) || empty($cardPwd)) {
            return $this->error('卡号和密码不能为空');
        }

        // 查找卡密
        $card = CardKey::where('card_no', $cardNo)->find();

        if (!$card) {
            return $this->error('卡密不存在');
        }

        // 检查密码
        if ($card->card_pwd !== $cardPwd) {
            return $this->error('卡密密码错误');
        }

        // 检查是否可用
        if (!$card->isAvailable()) {
            return $this->error('卡密已使用或已过期');
        }

        // 获取充值天数
        $days = CardKey::TYPE_DAYS[$card->type] ?? $card->days;

        // 获取用户
        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在');
        }

        // 永久VIP特殊处理
        if ($card->type == CardKey::TYPE_FOREVER) {
            $user->vip_status = User::VIP_ACTIVE;
            $user->vip_expire_time = null;
            $user->save();

            // 记录VIP变动
            $trans = new VipTransaction();
            $trans->user_id = $user->id;
            $trans->type = VipTransaction::TYPE_CARD;
            $trans->sub_type = 'forever';
            $trans->days = 0;
            $trans->related_id = $card->id;
            $trans->description = '永久VIP卡密兑换';
            $trans->save();
        } else {
            // 使用添加VIP天数的方法
            $user->addVipDays($days, VipTransaction::TYPE_CARD, $card->type_name, $card->id, '卡密兑换');
        }

        // 更新卡密状态
        $card->status = CardKey::STATUS_USED;
        $card->used_user_id = $userId;
        $card->used_at = date('Y-m-d H:i:s');
        $card->save();

        return $this->success([
            'vip_status' => $user->vip_status,
            'vip_expire_time' => $user->vip_expire_time,
            'vip_remain_days' => $user->getVipRemainDays(),
            'card_type_name' => $card->type_name,
            'days' => $days,
        ], '兑换成功');
    }

    /**
     * 生成卡密（管理端接口，这里先预留）
     */
    public function generate()
    {
        $data = $this->getData();

        $type = intval($data['type'] ?? 1);
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
            $card->days = CardKey::TYPE_DAYS[$type] ?? 1;
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
