<?php
namespace app\model;

use think\Model;

/**
 * 剧集模型（video_sources 表的别名）
 */
class Episode extends Model
{
    protected $name = 'video_sources';

    protected $type = [
        'last_sync_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 关联视频
    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    // 关联资源站
    public function sourceSite()
    {
        return $this->belongsTo(SourceSite::class, 'source_site_id');
    }
}
