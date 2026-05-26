<?php
namespace app\model;

use think\Model;

/**
 * 观看历史模型
 */
class WatchHistory extends Model
{
    protected $name = 'watch_history';

    protected $type = [
        'user_id' => 'integer',
        'video_id' => 'integer',
        'episode_id' => 'integer',
        'progress' => 'integer',
        'duration' => 'integer',
        'last_position' => 'integer',
        'watched_at' => 'datetime',
    ];

    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 关联视频
    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    // 关联剧集（实际上是 video_sources 表）
    public function episode()
    {
        return $this->belongsTo(VideoSource::class, 'episode_id');
    }
}
