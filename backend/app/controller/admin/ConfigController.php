<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\SystemConfig;
use app\model\VipTransaction;
use app\model\User;

/**
 * 管理端 - 系统配置
 */
class ConfigController extends BaseController
{
    /**
     * 获取配置列表
     */
    public function list()
    {
        $list = SystemConfig::order('id', 'asc')->select();
        
        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'key' => $item->key,
                'value' => $item->value,
                'type' => $item->type,
                'description' => $item->description,
            ];
        }

        return $this->success($result);
    }

    /**
     * 保存配置
     */
    public function save()
    {
        $data = $this->getData();
        $configs = $data['configs'] ?? [];

        if (!is_array($configs) || empty($configs)) {
            return $this->error('配置数据格式错误');
        }

        foreach ($configs as $config) {
            $key = $config['key'] ?? '';
            $value = $config['value'] ?? '';
            $type = $config['type'] ?? 'string';
            $description = $config['description'] ?? '';

            if (empty($key)) {
                continue;
            }

            SystemConfig::setValue($key, $value, $type, $description);
        }

        return $this->success(null, '保存成功');
    }

    /**
     * VIP变动记录列表
     */
    public function vipLogs()
    {
        $data = $this->getData();
        $keyword = trim($data['keyword'] ?? '');
        $type = trim($data['type'] ?? '');
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(100, intval($data['limit'] ?? 20)));

        $query = VipTransaction::with(['user']);

        if (!empty($keyword)) {
            $query->whereHas('user', function($q) use ($keyword) {
                $q->where('email', 'like', "%{$keyword}%");
            });
        }
        if (!empty($type)) {
            $query->where('type', $type);
        }

        $list = $query->order('created_at', 'desc')
            ->page($page, $limit)
            ->select();

        $total = $query->count();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'email' => $item->user ? $item->user->email : '用户已删除',
                'type' => $item->type,
                'type_name' => $item->type_name,
                'sub_type' => $item->sub_type,
                'days' => $item->days,
                'description' => $item->description,
                'created_at' => $item->created_at,
            ];
        }

        return $this->success([
            'list' => $result,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }
}
