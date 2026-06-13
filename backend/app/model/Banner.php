<?php
namespace app\model;

use think\Model;

class Banner extends Model
{
    protected $table = 'banners';
    
    protected $autoWriteTimestamp = true;
    
    const TYPE_VIDEO = 1;
    const TYPE_AD = 2;
    
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }
}