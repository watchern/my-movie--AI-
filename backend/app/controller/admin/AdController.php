<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\Ad;
use app\model\AdminLog;
use think\facade\Cache;

class AdController extends BaseController
{
    public function list()
    {
        $type = intval($this->request->get('type', 0));
        $query = Ad::order('sort_order', 'asc');
        if ($type > 0) {
            $query->where('type', $type);
        }
        $list = $query->select();
        return $this->success($list);
    }

    public function save()
    {
        $data = $this->getData();
        $id = intval($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $type = intval($data['type'] ?? 1);
        $imageBase64 = $data['image_base64'] ?? '';
        $linkUrl = trim($data['link_url'] ?? '');
        $sortOrder = intval($data['sort_order'] ?? 100);
        $status = intval($data['status'] ?? 1);

        if (empty($name)) {
            return $this->error('广告名称不能为空');
        }
        if (empty($imageBase64)) {
            return $this->error('广告图片不能为空');
        }

        if ($id > 0) {
            $ad = Ad::find($id);
            if (!$ad) {
                return $this->error('广告不存在');
            }
        } else {
            $ad = new Ad();
        }

        $ad->name = $name;
        $ad->type = $type;
        $ad->image_base64 = $imageBase64;
        $ad->link_url = $linkUrl;
        $ad->sort_order = $sortOrder;
        $ad->status = $status;
        $ad->save();

        $adminId = session('admin_id') ?? 0;
        $actionText = $id > 0 ? '编辑' : '添加';
        $typeText = $type === Ad::TYPE_PAUSE ? '暂停广告' : '结束广告';
        AdminLog::record($adminId, AdminLog::TYPE_OTHER, "{$typeText}「{$name}」(ID:{$ad->id}) - {$actionText}");

        Cache::delete('ads_type_' . $type);

        return $this->success($ad);
    }

    public function delete()
    {
        $data = $this->getData();
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return $this->error('请选择要删除的广告');
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ads = Ad::whereIn('id', $ids)->select();
        $names = array_column($ads->toArray(), 'name');

        Ad::whereIn('id', $ids)->delete();

        $adminId = session('admin_id') ?? 0;
        AdminLog::record($adminId, AdminLog::TYPE_OTHER, "删除广告: " . implode(', ', $names));

        Cache::delete('ads_type_' . Ad::TYPE_PAUSE);
        Cache::delete('ads_type_' . Ad::TYPE_END);

        return $this->success(null, '删除成功');
    }

    public function updateStatus()
    {
        $data = $this->getData();
        $id = intval($data['id'] ?? 0);
        $status = intval($data['value'] ?? 0);

        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $ad = Ad::find($id);
        if (!$ad) {
            return $this->error('广告不存在');
        }

        $ad->status = $status;
        $ad->save();

        $adminId = session('admin_id') ?? 0;
        $actionText = $status ? '启用' : '禁用';
        $typeText = $ad->type === Ad::TYPE_PAUSE ? '暂停广告' : '结束广告';
        AdminLog::record($adminId, AdminLog::TYPE_OTHER, "{$typeText}「{$ad->name}」(ID:{$id}) - {$actionText}");

        Cache::delete('ads_type_' . $ad->type);

        return $this->success(null, '更新成功');
    }

    public function getAds()
    {
        $type = intval($this->request->get('type', 1));
        $cacheKey = 'ads_type_' . $type;
        $ads = Cache::get($cacheKey);

        if (!$ads) {
            $ads = Ad::getActiveAds($type);
            Cache::set($cacheKey, $ads, 3600);
        }

        return $this->success($ads);
    }
}
