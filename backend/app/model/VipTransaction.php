<?php
namespace app\model;

use think\Model;

/**
 * VIP变动记录模型
 */
class VipTransaction extends Model
{
    protected $name = 'vip_transactions';

    protected $type = [
        'created_at' => 'datetime',
    ];

    // 类型常量
    const TYPE_CARD = 'card';        // 卡密兑换
    const TYPE_AD = 'ad';            // 广告观看
    const TYPE_ADMIN = 'admin';      // 管理员操作
    const TYPE_OTHER = 'other';     // 其他

    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 获取类型名称
    public function getTypeNameAttr()
    {
        $typeMap = [
            self::TYPE_CARD => '兑换码',
            self::TYPE_AD => '广告',
            self::TYPE_ADMIN => '管理员',
            self::TYPE_OTHER => '其他',
        ];
        return $typeMap[$this->type] ?? '未知';
    }
}
