<?php
namespace app\model;

use think\Model;

/**
 * 登录日志模型
 */
class LoginLog extends Model
{
    protected $name = 'login_logs';

    const DEVICE_MOBILE = 'mobile';
    const DEVICE_TABLET = 'tablet';
    const DEVICE_DESKTOP = 'desktop';
    const DEVICE_OTHER = 'other';

    protected $type = [
        'login_at' => 'datetime',
    ];

    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 从UA识别设备类型
    public static function detectDevice($userAgent): string
    {
        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return self::DEVICE_TABLET;
        }
        if (preg_match('/mobile|iphone|ipod|android|blackberry|opera mini|iemobile/i', $userAgent)) {
            return self::DEVICE_MOBILE;
        }
        if (preg_match('/windows|macintosh|linux|x11/i', $userAgent)) {
            return self::DEVICE_DESKTOP;
        }
        return self::DEVICE_OTHER;
    }

    // 获取设备名称
    public static function getDeviceName($device): string
    {
        $names = [
            self::DEVICE_MOBILE => '手机',
            self::DEVICE_TABLET => '平板',
            self::DEVICE_DESKTOP => '电脑',
            self::DEVICE_OTHER => '其他',
        ];
        return $names[$device] ?? '未知';
    }
}
