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

    // 获取格式化观看进度
    public function getProgressTextAttr()
    {
        $last = $this->last_position;
        $total = $this->duration;
        
        if ($total > 0) {
            $percent = round(($last / $total) * 100, 1);
            $lastText = $this->formatTime($last);
            $totalText = $this->formatTime($total);
            return "{$lastText} / {$totalText} ({$percent}%)";
        }
        
        return $this->formatTime($last);
    }

    // 格式化时间
    private function formatTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%d:%02d', $minutes, $secs);
    }
}
