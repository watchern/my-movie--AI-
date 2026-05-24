<?php
namespace app\model;

use think\Model;

/**
 * 影视内容模型
 */
class Video extends Model
{
    protected $name = 'videos';

    protected $type = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 关联分类
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // 关联视频资源
    public function videoSources()
    {
        return $this->hasMany(VideoSource::class, 'video_id');
    }

    // 获取器：演员JSON转换
    public function getActorsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 设置器：演员JSON转换
    public function setActorsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    // 获取器：标签JSON转换
    public function getTagsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 设置器：标签JSON转换
    public function setTagsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
}
