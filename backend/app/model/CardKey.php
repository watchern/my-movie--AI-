<?php
namespace app\model;

use think\Model;

/**
 * 兑换码模型
 */
class CardKey extends Model
{
    protected $name = 'card_keys';

    // 卡密类型
    const TYPE_DAY = 1;      // 天卡
    const TYPE_WEEK = 2;     // 周卡
    const TYPE_MONTH = 3;    // 月卡
    const TYPE_QUARTER = 4;  // 季卡
    const TYPE_YEAR = 5;     // 年卡
    const TYPE_FOREVER = 6;  // 永久

    // 状态
    const STATUS_UNUSED = 0;   // 未使用
    const STATUS_USED = 1;     // 已使用
    const STATUS_EXPIRED = 2;  // 已过期

    // 类型名称
    const TYPE_NAMES = [
        1 => '天卡',
        2 => '周卡',
        3 => '月卡',
        4 => '季卡',
        5 => '年卡',
        6 => '永久',
    ];

    // 类型天数映射
    const TYPE_DAYS = [
        1 => 1,
        2 => 7,
        3 => 30,
        4 => 90,
        5 => 365,
        6 => 36500,
    ];

    // 获取类型名称
    public function getTypeNameAttr()
    {
        $type = $this->getData('type');
        return self::TYPE_NAMES[$type] ?? '未知';
    }

    // 检查是否可用
    public function isAvailable(): bool
    {
        if ($this->status != self::STATUS_UNUSED) {
            return false;
        }
        if ($this->expired_at && strtotime($this->expired_at) < time()) {
            return false;
        }
        return true;
    }
}
