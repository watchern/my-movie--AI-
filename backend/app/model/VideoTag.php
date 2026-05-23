<?php
namespace app\model;

use think\Model;

/**
 * 影视标签关联模型
 */
class VideoTag extends Model
{
    protected $name = 'video_tags';

    // 关联视频
    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    // 关联标签
    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}
