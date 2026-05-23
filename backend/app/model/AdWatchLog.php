<?php
namespace app\model;

use think\Model;

/**
 * 广告观看记录模型
 */
class AdWatchLog extends Model
{
    protected $name = 'ad_watch_logs';

    protected $type = [
        'user_id' => 'integer',
        'reward' => 'integer',
        'created_at' => 'datetime',
    ];
}
