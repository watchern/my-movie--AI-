<?php
namespace app\model;

use think\Model;

/**
 * 影视内容模型
 */
class Video extends Model
{
    protected $name = 'videos';

    // 类型常量
    const TYPE_MOVIE = 1;      // 电影
    const TYPE_TV = 2;        // 电视剧
    const TYPE_ANIME = 3;     // 动漫
    const TYPE_SHORT = 4;     // 短视频

    // 类型名称映射
    const TYPE_NAMES = [
        1 => '电影',
        2 => '电视剧',
        3 => '动漫',
        4 => '短视频',
    ];

    // 类型转换
    protected $type = [
        'type' => 'integer',
        'category_id' => 'integer',
        'is_vip' => 'integer',
        'is_show' => 'integer',
        'play_count' => 'integer',
        'rating' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 获取类型名称
    public function getTypeNameAttr(): string
    {
        return self::TYPE_NAMES[$this->type] ?? '未知';
    }

    // 获取演员列表
    public function getActorsListAttr()
    {
        if (empty($this->actors)) {
            return [];
        }
        return json_decode($this->actors, true) ?? [];
    }

    // 获取封面URL
    public function getCoverUrlAttr(): string
    {
        if (empty($this->cover)) {
            return '';
        }
        // 如果已经是完整URL，直接返回
        if (strpos($this->cover, 'http') === 0) {
            return $this->cover;
        }
        return asset($this->cover);
    }

    // 获取播放地址
    public function getPlayUrlAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        return json_decode($value, true) ?? [];
    }

    // 设置播放地址
    public function setPlayUrlAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value;
    }

    // 关联分类
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // 关联剧集
    public function episodes()
    {
        return $this->hasMany(Episode::class, 'video_id');
    }
}
