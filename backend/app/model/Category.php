<?php
namespace app\model;

use think\Model;

/**
 * 影视分类模型
 */
class Category extends Model
{
    protected $name = 'categories';

    // 类型常量
    const TYPE_MOVIE = 1;
    const TYPE_TV = 2;
    const TYPE_ANIME = 3;
    const TYPE_SHORT = 4;

    // 类型转换
    protected $type = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'type' => 'integer',
    ];

    // 获取类型名称
    public function getTypeNameAttr(): string
    {
        $names = [
            1 => '电影',
            2 => '电视剧',
            3 => '动漫',
            4 => '短视频',
        ];
        return $names[$this->type] ?? '未知';
    }

    // 获取父级分类
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // 获取子分类
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
