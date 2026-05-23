<?php
namespace app\model;

use think\Model;

/**
 * VIP变动记录模型
 */
class VipTransaction extends Model
{
    protected $name = 'vip_transactions';

    const TYPE_CARD = 'card';
    const TYPE_AD = 'ad';
    const TYPE_OTHER = 'other';

    protected $type = [
        'created_at' => 'datetime',
    ];

    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
