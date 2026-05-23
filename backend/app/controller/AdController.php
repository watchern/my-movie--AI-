<?php
namespace app\controller;

use app\BaseController;
use app\model\User;
use app\model\VipTransaction;
use app\model\SystemConfig;
use think\facade\Db;

/**
 * 广告控制器
 */
class AdController extends BaseController
{
    /**
     * 记录广告观看
     */
    public function watch()
    {
        $userId = $this->request->uid ?? 0;
        $data = $this->getData();

        $adType = trim($data['ad_type'] ?? 'video');

        if ($userId <= 0) {
            return $this->error('请先登录');
        }

        // 获取广告奖励配置
        $rewardMinutes = intval(SystemConfig::getValue('ad_video_reward', 30));
        $dailyLimit = intval(SystemConfig::getValue('ad_daily_limit', 10));

        // 检查今日已观看次数 - 使用vip_transactions表
        $todayCount = VipTransaction::where('user_id', $userId)
            ->where('type', VipTransaction::TYPE_AD)
            ->whereTime('created_at', 'today')
            ->count();

        if ($todayCount >= $dailyLimit) {
            return $this->error('今日广告观看次数已用完');
        }

        // 获取用户
        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在');
        }

        // 计算天数(向上取整)
        $days = ceil($rewardMinutes / 1440);

        // 添加VIP天数
        $user->addVipDays($days, VipTransaction::TYPE_AD, $adType, null, '观看广告奖励');

        return $this->success([
            'reward_minutes' => $rewardMinutes,
            'today_count' => $todayCount + 1,
            'daily_limit' => $dailyLimit,
            'vip_expire_time' => $user->vip_expire_time,
        ], '观看成功');
    }

    /**
     * 获取今日观看状态
     */
    public function status()
    {
        $userId = $this->request->uid ?? 0;

        $dailyLimit = intval(SystemConfig::getValue('ad_daily_limit', 10));

        $todayCount = 0;
        if ($userId > 0) {
            $todayCount = VipTransaction::where('user_id', $userId)
                ->where('type', VipTransaction::TYPE_AD)
                ->whereTime('created_at', 'today')
                ->count();
        }

        return $this->success([
            'today_count' => $todayCount,
            'daily_limit' => $dailyLimit,
            'remain_count' => max(0, $dailyLimit - $todayCount),
        ]);
    }
}
