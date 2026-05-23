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

    /**
     * 获取配置值
     */
    public static function getValue(string $key, $default = null)
    {
        $config = self::where('key', $key)->find();
        if (!$config) {
            return $default;
        }

        $value = $config->value;
        $type = $config->type ?? 'string';

        switch ($type) {
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'bool':
                return in_array(strtolower($value), ['1', 'true', 'yes']) ? true : false;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * 设置配置值
     */
    public static function setValue(string $key, $value, string $type = 'string')
    {
        $config = self::where('key', $key)->find();

        if (!$config) {
            $config = new self();
            $config->key = $key;
        }

        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            $type = 'json';
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
            $type = 'bool';
        }

        $config->value = strval($value);
        $config->type = $type;
        $config->save();

        return true;
    }
}
