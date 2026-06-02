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
    const TYPE_CARD = 'card';          // 用户兑换码兑换
    const TYPE_AD = 'ad';              // 广告观看
    const TYPE_ADMIN = 'admin';        // 管理员手动调整
    const TYPE_CARD_DISABLE = 'card_disable';  // 兑换码失效
    const TYPE_CARD_DELETE = 'card_delete';    // 兑换码删除
    const TYPE_CARD_GENERATE = 'card_generate'; // 兑换码生成
    const TYPE_OTHER = 'other';       // 其他

    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 获取类型名称
    public function getTypeNameAttr()
    {
        $typeMap = [
            self::TYPE_CARD => '兑换码兑换',
            self::TYPE_AD => '广告',
            self::TYPE_ADMIN => '管理员调整',
            self::TYPE_CARD_DISABLE => '兑换码失效',
            self::TYPE_CARD_DELETE => '兑换码删除',
            self::TYPE_CARD_GENERATE => '兑换码生成',
            self::TYPE_OTHER => '其他',
        ];
        return $typeMap[$this->type] ?? '未知';
    }
}
