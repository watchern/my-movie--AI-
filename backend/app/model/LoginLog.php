<?php
namespace app\model;

use think\Model;

/**
 * 登录日志模型
 */
class LoginLog extends Model
{
    protected $name = 'login_logs';

    protected $type = [
        'login_at' => 'datetime',
    ];

    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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

    // 检测设备类型
    public static function detectDevice(string $userAgent = ''): string
    {
        if (empty($userAgent)) {
            return 'other';
        }

        $userAgent = strtolower($userAgent);

        if (strpos($userAgent, 'mobile') !== false || strpos($userAgent, 'android') !== false || strpos($userAgent, 'iphone') !== false) {
            if (strpos($userAgent, 'ipad') !== false || strpos($userAgent, 'tablet') !== false) {
                return 'tablet';
            }
            return 'mobile';
        }

        return 'desktop';
    }
}
