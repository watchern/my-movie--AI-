<?php
namespace app\model;

use think\Model;

/**
 * 资源站点配置模型
 */
class SourceSite extends Model
{
    protected $name = 'source_sites';

    protected $type = [
        'last_sync_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
