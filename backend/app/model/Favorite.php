<?php
namespace app\model;

use think\Model;

/**
 * 收藏模型
 */
class Favorite extends Model
{
    protected $name = 'favorites';

    protected $type = [
        'created_at' => 'datetime',
    ];

    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 关联视频
    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }
}
