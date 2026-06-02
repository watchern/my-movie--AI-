<?php
namespace app\model;

use think\Model;

/**
 * 管理员模型
 */
class Admin extends Model
{
    protected $name = 'admins';

    protected $type = [
        'last_login_time' => 'datetime',
        'created_at' => 'datetime',
    ];

    // 状态常量
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    // 隐藏字段
    protected $hidden = ['password'];

    // 设置密码（加密）
    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    // 验证密码
    public function checkPassword($password)
    {
        return password_verify($password, $this->password);
    }

    // 获取状态名称
    public function getStatusNameAttr()
    {
        return $this->status === self::STATUS_ENABLED ? '启用' : '禁用';
    }
}
