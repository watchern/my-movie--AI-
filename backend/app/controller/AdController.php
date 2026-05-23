<?php
namespace app\controller;

use app\BaseController;
use app\model\AdWatchLog;
use app\model\User;
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

        // 检查今日已观看次数
        $today = date('Y-m-d');
        $todayCount = AdWatchLog::where('user_id', $userId)
            ->whereTime('created_at', 'today')
            ->count();

        if ($todayCount >= $dailyLimit) {
            return $this->error('今日广告观看次数已用完');
        }

        // 记录广告观看
        $log = new AdWatchLog();
        $log->user_id = $userId;
        $log->ad_type = $adType;
        $log->reward = $rewardMinutes;
        $log->save();

        // 给用户增加VIP时长
        $user = User::find($userId);
        if ($user) {
            $now = time();

            if ($user->vip_status == User::VIP_ACTIVE && !empty($user->vip_expire_time)) {
                $expireTime = strtotime($user->vip_expire_time);
                if ($expireTime > $now) {
                    $newExpireTime = date('Y-m-d H:i:s', $expireTime + ($rewardMinutes * 60));
                } else {
                    $newExpireTime = date('Y-m-d H:i:s', $now + ($rewardMinutes * 60));
                }
            } else {
                // 用户没有VIP或VIP已过期，开通VIP
                $newExpireTime = date('Y-m-d H:i:s', $now + ($rewardMinutes * 60));
            }

            $user->vip_status = User::VIP_ACTIVE;
            $user->vip_expire_time = $newExpireTime;
            $user->save();
        }

        return $this->success([
            'reward_minutes' => $rewardMinutes,
            'today_count' => $todayCount + 1,
            'daily_limit' => $dailyLimit,
            'vip_expire_time' => $user ? $user->vip_expire_time : null,
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
            $todayCount = AdWatchLog::where('user_id', $userId)
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
