<?php
namespace app\model;

use think\Model;

/**
 * 卡密充值记录模型
 */
class CardRechargeLog extends Model
{
    protected $name = 'card_recharge_logs';

    protected $type = [
        'user_id' => 'integer',
        'card_id' => 'integer',
        'days' => 'integer',
        'created_at' => 'datetime',
    ];
}
