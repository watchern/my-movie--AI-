<?php
namespace app\model;

use think\Model;

/**
 * 剧集模型
 */
class Episode extends Model
{
    protected $name = 'episodes';

    protected $type = [
        'created_at' => 'datetime',
    ];

    // 关联视频资源
    public function videoSource()
    {
        return $this->belongsTo(VideoSource::class, 'video_source_id');
    }
}
