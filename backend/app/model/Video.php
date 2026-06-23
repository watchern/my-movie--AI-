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
    const TYPE_MOVIE = 1;     // 电影
    const TYPE_TV = 2;        // 电视剧
    const TYPE_ANIME = 3;     // 动漫
    const TYPE_SHORT = 4;     // 短视频
    const TYPE_DOCUMENTARY = 5; // 纪录片

    // 类型转换
    protected $type = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 自动时间戳字段名
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

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

    // 剧集关联（videoSources 的别名）
    public function episodes()
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

    // 获取器：封面URL
    public function getCoverUrlAttr()
    {
        return $this->cover ?? '';
    }

    // 获取器：类型名称
    public function getTypeNameAttr()
    {
        $typeMap = [
            self::TYPE_MOVIE => '电影',
            self::TYPE_TV => '电视剧',
            self::TYPE_ANIME => '动漫',
            self::TYPE_SHORT => '短视频',
            self::TYPE_DOCUMENTARY => '纪录片',
        ];
        // 使用 getData('type') 获取原始类型值，避免与 $type 属性冲突
        $videoType = $this->getData('type');
        return $typeMap[$videoType] ?? '电影';
    }
}
