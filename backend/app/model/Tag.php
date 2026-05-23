<?php
namespace app\model;

use think\Model;

/**
 * 影视标签模型
 */
class Tag extends Model
{
    protected $name = 'tags';

    protected $type = [
        'created_at' => 'datetime',
    ];

    // 关联视频
    public function videos()
    {
        return $this->belongsToMany(Video::class, 'video_tags', 'video_id', 'tag_id');
    }
}
