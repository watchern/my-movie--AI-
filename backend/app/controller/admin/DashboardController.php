<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\User;
use app\model\Video;
use app\model\CardKey;
use app\model\WatchHistory;
use think\facade\Db;

/**
 * 管理端仪表盘
 */
class DashboardController extends BaseController
{
    /**
     * 获取统计数据
     */
    public function stats()
    {
        $data = $this->getData();
        $type = isset($data['type']) && $data['type'] !== '' && $data['type'] !== null ? intval($data['type']) : 0;

        $today = date('Y-m-d');

        // 会员统计
        $vipCount = User::where('vip_status', 1)->count();
        $totalUsers = User::count();

        // 视频统计
        $totalVideos = Video::count();
        $visibleVideos = Video::where('is_show', 1)->count();
        $movieCount = Video::where('is_show', 1)->where('type', 1)->count();
        $tvCount = Video::where('is_show', 1)->where('type', 2)->count();
        $animeCount = Video::where('is_show', 1)->where('type', 3)->count();
        $shortCount = Video::where('is_show', 1)->where('type', 4)->count();

        // 卡密统计
        $unusedCards = CardKey::where('status', 0)->count();
        $usedCards = CardKey::where('status', 1)->count();
        $totalCards = $unusedCards + $usedCards;

        // 今日数据
        $todayNewUsers = User::where('created_at', '>=', $today . ' 00:00:00')->count();
        $todayWatchHistory = WatchHistory::where('watched_at', '>=', $today . ' 00:00:00')->count();

        // 播放排行TOP10
        $videoQuery = Video::where('is_show', 1);
        if ($type > 0) {
            $videoQuery->where('type', $type);
        }
        $topVideos = $videoQuery
            ->order('play_count', 'desc')
            ->limit(10)
            ->select();

        $topVideoList = [];
        foreach ($topVideos as $item) {
            $topVideoList[] = [
                'id' => $item->id,
                'title' => $item->title,
                'cover' => $item->cover,
                'play_count' => $item->play_count,
                'type_name' => $item->type_name,
            ];
        }

        return $this->success([
            'user' => [
                'vip' => $vipCount,
                'total' => $totalUsers,
                'today_new' => $todayNewUsers,
            ],
            'video' => [
                'visible' => $visibleVideos,
                'total' => $totalVideos,
                'movie' => $movieCount,
                'tv' => $tvCount,
                'anime' => $animeCount,
                'short' => $shortCount,
            ],
            'card' => [
                'unused' => $unusedCards,
                'total' => $totalCards,
            ],
            'today' => [
                'watch_history' => $todayWatchHistory,
            ],
            'top_videos' => $topVideoList,
        ]);
    }
}
