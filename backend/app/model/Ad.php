<?php
namespace app\model;

use think\Model;

class Ad extends Model
{
    protected $name = 'ads';

    const TYPE_PAUSE = 1;
    const TYPE_END = 2;

    protected $type = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] ?? 1 ? '启用' : '禁用';
    }

    public function getTypeTextAttr($value, $data)
    {
        $map = [
            self::TYPE_PAUSE => '暂停广告',
            self::TYPE_END => '结束广告',
        ];
        return $map[$data['type'] ?? 1] ?? '未知';
    }

    public static function getActiveAds(int $type): array
    {
        return self::where('type', $type)
            ->where('status', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
    }
}
