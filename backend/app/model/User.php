<?php
namespace app\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 用户模型
 */
class User extends Model
{
    use SoftDelete;

    protected $name = 'users';
    protected $deleteTime = 'deleted_at';
    protected $defaultSoftDelete = null;

    // VIP状态常量
    const VIP_NORMAL = 0;
    const VIP_ACTIVE = 1;

    // 类型转换
    protected $type = [
        'vip_status' => 'integer',
        'vip_expire_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 获取器：密码隐藏
    public function getPasswordAttr($value)
    {
        return '';
    }

    // 设置器：密码加密
    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    // 检查VIP是否有效
    public function isVipValid(): bool
    {
        if ($this->vip_status != self::VIP_ACTIVE) {
            return false;
        }
        if (empty($this->vip_expire_time)) {
            return true; // 永久VIP
        }
        return strtotime($this->vip_expire_time) > time();
    }

    // 获取VIP剩余天数
    public function getVipRemainDays(): int
    {
        if ($this->vip_status != self::VIP_ACTIVE) {
            return 0;
        }
        if (empty($this->vip_expire_time)) {
            return 9999; // 永久
        }
        $remain = strtotime($this->vip_expire_time) - time();
        return max(0, floor($remain / 86400));
    }
}
