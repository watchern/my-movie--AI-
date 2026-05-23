<?php
namespace app\controller;

use app\BaseController;
use app\model\WatchHistory;
use app\model\Video;
use app\model\User;

/**
 * 观看历史控制器
 */
class HistoryController extends BaseController
{
    /**
     * 获取历史记录列表
     */
    public function list()
    {
        $userId = $this->request->uid ?? 0;
        list($page, $limit) = $this->getPageParams();

        $list = WatchHistory::with(['video', 'episode'])
            ->where('user_id', $userId)
            ->order('watched_at', 'desc')
            ->page($page, $limit)
            ->select();

        $total = WatchHistory::where('user_id', $userId)->count();

        $result = [];
        foreach ($list as $item) {
            if ($item->video) {
                $result[] = [
                    'id' => $item->id,
                    'video_id' => $item->video_id,
                    'episode_id' => $item->episode_id,
                    'title' => $item->video->title,
                    'cover' => $item->video->cover_url,
                    'type' => $item->video->type,
                    'type_name' => $item->video->type_name,
                    'progress' => $item->progress,
                    'duration' => $item->duration,
                    'last_position' => $item->last_position,
                    'watched_at' => $item->watched_at,
                    'episode_title' => $item->episode ? '第' . $item->episode->episode_number . '集' : '',
                ];
            }
        }

        return $this->success([
            'list' => $result,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 添加/更新观看历史
     */
    public function add()
    {
        $userId = $this->request->uid ?? 0;
        $data = $this->getData();

        $videoId = intval($data['video_id'] ?? 0);
        $episodeId = intval($data['episode_id'] ?? 0);
        $progress = intval($data['progress'] ?? 0); // 百分比 0-100
        $lastPosition = intval($data['last_position'] ?? 0); // 秒
        $duration = intval($data['duration'] ?? 0); // 视频总时长(秒)

        if ($videoId <= 0) {
            return $this->error('参数错误');
        }

        // 检查视频是否存在
        $video = Video::find($videoId);
        if (!$video || $video->is_show != 1) {
            return $this->error('视频不存在');
        }

        // 如果传了episode_id，检查剧集是否存在
        if ($episodeId > 0) {
            $episode = \app\model\Episode::where('id', $episodeId)
                ->where('video_id', $videoId)
                ->find();
            if (!$episode) {
                return $this->error('剧集不存在');
            }
        }

        // 查找或创建历史记录
        $where = [
            'user_id' => $userId,
            'video_id' => $videoId,
        ];
        if ($episodeId > 0) {
            $where['episode_id'] = $episodeId;
        } else {
            $where['episode_id'] = 0;
        }

        $history = WatchHistory::where($where)->find();

        if (!$history) {
            $history = new WatchHistory();
            $history->user_id = $userId;
            $history->video_id = $videoId;
            $history->episode_id = $episodeId ?: 0;
        }

        $history->progress = $progress;
        $history->last_position = $lastPosition;
        $history->duration = $duration;
        $history->watched_at = date('Y-m-d H:i:s');
        $history->save();

        // 更新用户累计观看时长
        $user = User::find($userId);
        if ($user && $duration > 0) {
            // 每观看10分钟累加一次
            $user->total_watch_time = $user->total_watch_time + ceil($duration / 600);
            $user->save();
        }

        return $this->success([
            'id' => $history->id,
            'progress' => $history->progress,
            'last_position' => $history->last_position,
        ], '记录成功');
    }

    /**
     * 清空观看历史
     */
    public function clear()
    {
        $userId = $this->request->uid ?? 0;
        WatchHistory::where('user_id', $userId)->delete();
        return $this->success(null, '已清空');
    }

    /**
     * 删除单条历史记录
     */
    public function delete()
    {
        $userId = $this->request->uid ?? 0;
        $data = $this->getData();
        $id = intval($data['id'] ?? 0);

        if ($id > 0) {
            WatchHistory::where('id', $id)
                ->where('user_id', $userId)
                ->delete();
        }

        return $this->success(null, '已删除');
    }
}
