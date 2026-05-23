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
        'video_id' => 'integer',
        'episode_number' => 'integer',
        'duration' => 'integer',
        'sort_order' => 'integer',
    ];

    // 获取播放地址
    public function getPlayUrlAttr($value)
    {
        return $value ?? '';
    }

    // 关联影视
    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }
}
