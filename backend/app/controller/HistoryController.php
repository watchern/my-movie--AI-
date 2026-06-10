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
                // 获取剧集名称
                $episodeName = '';
                if ($item->video_source_id) {
                    $source = \app\model\VideoSource::find($item->video_source_id);
                    if ($source) {
                        $episodeName = $source->name ?? '';
                    }
                }
                $result[] = [
                    'id' => $item->id,
                    'video_id' => $item->video_id,
                    'episode_id' => $item->video_source_id, // video_source_id 作为 episode_id 返回
                    'title' => $item->video->title,
                    'cover' => $item->video->cover_url,
                    'type' => $item->video->type,
                    'type_name' => $item->video->type_name,
                    'progress' => $item->progress,
                    'duration' => $item->duration,
                    'last_position' => $item->last_position,
                    'watched_at' => $item->watched_at,
                    'episode_title' => $episodeName,
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
        $videoSourceId = intval($data['episode_id'] ?? 0); // 前端传入的 episode_id，实际是 video_source_id
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

        // 如果传了 video_source_id，检查剧集是否存在
        if ($videoSourceId > 0) {
            $source = \app\model\VideoSource::where('id', $videoSourceId)
                ->where('video_id', $videoId)
                ->find();
            if (!$source) {
                return $this->error('剧集不存在');
            }
        }

        // 查找或创建历史记录
        $where = [
            'user_id' => $userId,
            'video_id' => $videoId,
        ];
        if ($videoSourceId > 0) {
            $where['video_source_id'] = $videoSourceId;
        } else {
            $where['video_source_id'] = 0;
        }

        $history = WatchHistory::where($where)->find();

        if (!$history) {
            $history = new WatchHistory();
            $history->user_id = $userId;
            $history->video_id = $videoId;
            $history->video_source_id = $videoSourceId ?: null;
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

    /**
     * 批量同步历史记录（从本地存储同步到服务器）
     */
    public function syncBatch()
    {
        $userId = $this->request->uid ?? 0;
        $data = $this->getData();
        
        $items = $data['items'] ?? [];
        
        if (empty($items)) {
            return $this->success(['synced' => 0], '无数据需要同步');
        }

        $synced = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $videoId = intval($item['video_id'] ?? 0);
            $videoSourceId = intval($item['episode_id'] ?? 0); // episode_id 前端传入，实际存 video_source_id
            $progress = intval($item['progress'] ?? 0);
            $lastPosition = intval($item['last_position'] ?? 0);
            $duration = intval($item['duration'] ?? 0);
            $watchedAt = $item['watched_at'] ?? $now;

            if ($videoId <= 0) continue;

            // 查找或创建历史记录
            $where = [
                'user_id' => $userId,
                'video_id' => $videoId,
            ];
            if ($videoSourceId > 0) {
                $where['video_source_id'] = $videoSourceId;
            }

            $history = WatchHistory::where($where)->find();

            if (!$history) {
                $history = new WatchHistory();
                $history->user_id = $userId;
                $history->video_id = $videoId;
                $history->video_source_id = $videoSourceId ?: null;
            }

            // 如果本地记录更新，则更新服务器数据
            if ($history->watched_at === null || strtotime($watchedAt) > strtotime($history->watched_at)) {
                $history->progress = $progress;
                $history->last_position = $lastPosition;
                $history->duration = $duration;
                $history->watched_at = $watchedAt;
                $history->save();
                $synced++;
            }
        }

        return $this->success(['synced' => $synced], "成功同步 {$synced} 条记录");
    }
}
