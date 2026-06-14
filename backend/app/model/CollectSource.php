<?php

namespace app\model;

use think\Model;

/**
 * 资源采集站点模型
 */
class CollectSource extends Model
{
    protected $name = 'collect_sources';

    // 类型转换
    protected $type = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 获取器：状态转换
    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] ?? 1 ? '启用' : '禁用';
    }
}
