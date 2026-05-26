<?php
namespace app\model;

use think\Model;

/**
 * 资源站点配置模型
 */
class SourceSite extends Model
{
    protected $name = 'source_sites';

    // 状态常量
    const STATUS_DISABLED = 0;   // 禁用
    const STATUS_ENABLED = 1;    // 启用

    protected $type = [
        'last_sync_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // 关联视频资源
    public function videoSources()
    {
        return $this->hasMany(VideoSource::class, 'source_site_id');
    }
}
