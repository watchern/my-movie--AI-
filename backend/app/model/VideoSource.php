<?php
namespace app\model;

use think\Model;

/**
 * 视频资源与播放列表模型
 */
class VideoSource extends Model
{
    protected $name = 'video_sources';

    protected $type = [
        'last_sync_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

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
