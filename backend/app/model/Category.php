<?php
namespace app\model;

use think\Model;

/**
 * 影视分类模型
 */
class Category extends Model
{
    protected $name = 'categories';

    protected $type = [
        'created_at' => 'datetime',
    ];
}
