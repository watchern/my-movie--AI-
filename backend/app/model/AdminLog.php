<?php
namespace app\model;

use think\Model;

/**
 * 管理员操作日志模型
 */
class AdminLog extends Model
{
    protected $name = 'admin_logs';

    protected $type = [
        'created_at' => 'datetime',
    ];

    // 操作类型常量
    const TYPE_LOGIN = 'login';
    const TYPE_LOGOUT = 'logout';
    const TYPE_ADD_ADMIN = 'add_admin';
    const TYPE_EDIT_ADMIN = 'edit_admin';
    const TYPE_DELETE_ADMIN = 'delete_admin';
    const TYPE_DISABLE_ADMIN = 'disable_admin';
    const TYPE_ENABLE_ADMIN = 'enable_admin';
    const TYPE_CHANGE_PASSWORD = 'change_password';
    const TYPE_ADD_VIDEO = 'add_video';
    const TYPE_EDIT_VIDEO = 'edit_video';
    const TYPE_DELETE_VIDEO = 'delete_video';
    const TYPE_ADD_CATEGORY = 'add_category';
    const TYPE_EDIT_CATEGORY = 'edit_category';
    const TYPE_DELETE_CATEGORY = 'delete_category';
    const TYPE_ADD_VIP_CARD = 'add_vip_card';
    const TYPE_DELETE_VIP_CARD = 'delete_vip_card';
    const TYPE_DISABLE_VIP_CARD = 'disable_vip_card';
    const TYPE_EDIT_CONFIG = 'edit_config';
    const TYPE_OTHER = 'other';

    // 关联管理员
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    // 获取操作类型名称
    public function getTypeNameAttr()
    {
        $typeMap = [
            self::TYPE_LOGIN => '登录',
            self::TYPE_LOGOUT => '登出',
            self::TYPE_ADD_ADMIN => '添加管理员',
            self::TYPE_EDIT_ADMIN => '编辑管理员',
            self::TYPE_DELETE_ADMIN => '删除管理员',
            self::TYPE_DISABLE_ADMIN => '禁用管理员',
            self::TYPE_ENABLE_ADMIN => '启用管理员',
            self::TYPE_CHANGE_PASSWORD => '修改密码',
            self::TYPE_ADD_VIDEO => '添加视频',
            self::TYPE_EDIT_VIDEO => '编辑视频',
            self::TYPE_DELETE_VIDEO => '删除视频',
            self::TYPE_ADD_CATEGORY => '添加分类',
            self::TYPE_EDIT_CATEGORY => '编辑分类',
            self::TYPE_DELETE_CATEGORY => '删除分类',
            self::TYPE_ADD_VIP_CARD => '生成兑换码',
            self::TYPE_DELETE_VIP_CARD => '删除兑换码',
            self::TYPE_DISABLE_VIP_CARD => '禁用兑换码',
            self::TYPE_EDIT_CONFIG => '修改配置',
            self::TYPE_OTHER => '其他操作',
        ];
        $type = is_object($this->type) ? (string)$this->type : $this->type;
        return $typeMap[$type] ?? '未知操作';
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

    /**
     * 记录管理员操作日志
     */
    public static function record(int $adminId, string $type, string $detail = '', string $ip = ''): bool
    {
        $log = new self();
        $log->admin_id = $adminId;
        $log->type = $type;
        $log->detail = $detail;
        $log->ip = $ip;
        $log->device_info = request()->header('User-Agent') ?? '';
        $log->save();
        return true;
    }
}