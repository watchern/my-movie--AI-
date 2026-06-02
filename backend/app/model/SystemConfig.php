<?php
namespace app\model;

use think\Model;

/**
 * 系统配置模型
 */
class SystemConfig extends Model
{
    protected $name = 'system_config';

    protected $type = [
        'updated_at' => 'datetime',
    ];

    // 获取配置值
    public static function getValue(string $key, $default = null)
    {
        $config = self::where('key', $key)->find();
        if (!$config) {
            return $default;
        }

        return self::parseValue($config->value, $config->type);
    }

    // 设置配置值
    public static function setValue(string $key, $value, string $type = 'string', string $description = '')
    {
        $config = self::where('key', $key)->find();
        
        if ($config) {
            $config->value = self::formatValue($value, $type);
            $config->save();
        } else {
            $config = new self();
            $config->key = $key;
            $config->value = self::formatValue($value, $type);
            $config->type = $type;
            $config->description = $description;
            $config->save();
        }

        return true;
    }

    // 获取所有配置
    public static function getAll()
    {
        $list = self::order('id', 'asc')->select();
        $result = [];
        foreach ($list as $item) {
            $result[$item->key] = self::parseValue($item->value, $item->type);
        }
        return $result;
    }

    // 解析值
    private static function parseValue($value, string $type)
    {
        switch ($type) {
            case 'int':
                return intval($value);
            case 'bool':
                return boolval($value);
            case 'json':
                return $value ? json_decode($value, true) : null;
            default:
                return $value;
        }
    }

    // 格式化值
    private static function formatValue($value, string $type)
    {
        switch ($type) {
            case 'json':
                return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
            case 'bool':
                return $value ? '1' : '0';
            default:
                return strval($value);
        }
    }
}
