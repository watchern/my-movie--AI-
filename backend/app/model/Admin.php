<?php
namespace app\model;

use think\Model;

/**
 * 管理员模型
 */
class Admin extends Model
{
    protected $name = 'admins';

    // 状态
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    protected $type = [
        'status' => 'integer',
        'last_login_time' => 'datetime',
        'created_at' => 'datetime',
    ];

    // 设置器：密码加密
    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }
}
