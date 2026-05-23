<?php
namespace app\model;

use think\Model;

/**
 * 资源站点模型
 */
class SourceSite extends Model
{
    protected $name = 'source_sites';

    // 状态
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    protected $type = [
        'status' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];
}
