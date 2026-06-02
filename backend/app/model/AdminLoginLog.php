<?php
namespace app\model;

use think\Model;

/**
 * 管理员登录日志模型
 */
class AdminLoginLog extends Model
{
    protected $name = 'admin_login_logs';

    protected $type = [
        'login_at' => 'datetime',
    ];

    // 关联管理员
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    // 获取设备名称
    public function getDeviceNameAttr()
    {
        $deviceMap = [
            'mobile' => '手机',
            'tablet' => '平板',
            'desktop' => '电脑',
            'other' => '其他',
        ];
        return $deviceMap[$this->device] ?? '未知';
    }
}
