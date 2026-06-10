<?php
namespace app\model;

use think\Model;

/**
 * 用户模型
 */
class User extends Model
{
    protected $name = 'users';

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

    // 获取器：密码获取时返回原值（用于验证）
    // 注意：返回原值会被 password_verify 正确处理
    public function getPasswordAttr($value)
    {
        return $value;
    }

    // 设置器：密码加密
    public function setPasswordAttr($value)
    {
        error_log("User::setPasswordAttr called with: " . $value);
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

    // 获取VIP剩余精确时间（格式：xx天xx小时xx分钟）
    public function getVipRemainTime(): string
    {
        if ($this->vip_status != self::VIP_ACTIVE) {
            return '0天0小时0分钟';
        }
        if (empty($this->vip_expire_time)) {
            return '永久';
        }
        $remain = strtotime($this->vip_expire_time) - time();
        if ($remain <= 0) {
            return '0天0小时0分钟';
        }
        $days = floor($remain / 86400);
        $hours = floor(($remain % 86400) / 3600);
        $minutes = floor(($remain % 3600) / 60);
        return "{$days}天{$hours}小时{$minutes}分钟";
    }

    // 添加VIP天数
    public function addVipDays(int $days, string $type = VipTransaction::TYPE_OTHER, string $subType = null, int $relatedId = null, string $description = null): bool
    {
        $now = time();

        // 检查是否永久VIP
        if ($this->vip_status == self::VIP_ACTIVE && empty($this->vip_expire_time)) {
            // 永久VIP，不需要更新
        } else {
            // 计算新的过期时间
            if ($this->vip_status == self::VIP_ACTIVE && !empty($this->vip_expire_time)) {
                $expireTime = strtotime($this->vip_expire_time);
                if ($expireTime > $now) {
                    // 在原有基础上增加
                    $newExpireTime = date('Y-m-d H:i:s', $expireTime + ($days * 86400));
                } else {
                    // 已过期，从现在开始
                    $newExpireTime = date('Y-m-d H:i:s', $now + ($days * 86400));
                }
            } else {
                // 没有VIP，从现在开始
                $newExpireTime = date('Y-m-d H:i:s', $now + ($days * 86400));
            }
            $this->vip_status = self::VIP_ACTIVE;
            $this->vip_expire_time = $newExpireTime;
        }

        if (!$this->save()) {
            return false;
        }

        // 记录VIP变动
        $trans = new VipTransaction();
        $trans->user_id = $this->id;
        $trans->type = $type;
        $trans->sub_type = $subType;
        $trans->days = $days;
        $trans->related_id = $relatedId;
        $trans->description = $description;
        $trans->save();

        return true;
    }

    // 记录登录日志
    public function recordLogin(string $ip = null, string $userAgent = null): bool
    {
        $log = new LoginLog();
        $log->user_id = $this->id;
        $log->login_ip = $ip;
        $log->device = LoginLog::detectDevice($userAgent ?? '');
        $log->device_info = $userAgent;
        $log->login_at = date('Y-m-d H:i:s');
        return $log->save();
    }
}
