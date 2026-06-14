<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\CollectSource;
use think\facade\Db;

/**
 * 资源采集站点管理控制器
 */
class CollectSourceController extends BaseController
{
    /**
     * 列表
     */
    public function list()
    {
        $page = max(1, intval($this->request->get('page', 1)));
        $limit = max(1, min(50, intval($this->request->get('limit', 20))));

        $query = CollectSource::order('id', 'desc');
        $list = $query->page($page, $limit)->select();
        $total = $query->count();

        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 添加
     */
    public function add()
    {
        $data = $this->getData();
        
        $name = trim($data['name'] ?? '');
        $api_url = trim($data['api_url'] ?? '');
        $site_type = intval($data['site_type'] ?? 1); // 1=苹果CMS, 2=其他
        $status = intval($data['status'] ?? 1);

        if (empty($name)) {
            return $this->error('站点名称不能为空');
        }
        if (empty($api_url)) {
            return $this->error('接口地址不能为空');
        }

        // 检查名称是否重复
        if (CollectSource::where('name', $name)->find()) {
            return $this->error('站点名称已存在');
        }

        $source = CollectSource::create([
            'name' => $name,
            'api_url' => $api_url,
            'site_type' => $site_type,
            'status' => $status,
        ]);

        return $this->success($source);
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $data = $this->getData();
        
        $id = intval($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $api_url = trim($data['api_url'] ?? '');
        $site_type = intval($data['site_type'] ?? 1);
        $status = intval($data['status'] ?? 1);

        if ($id <= 0) {
            return $this->error('参数错误');
        }
        if (empty($name)) {
            return $this->error('站点名称不能为空');
        }
        if (empty($api_url)) {
            return $this->error('接口地址不能为空');
        }

        // 检查名称是否重复（排除自己）
        if (CollectSource::where('name', $name)->where('id', '<>', $id)->find()) {
            return $this->error('站点名称已存在');
        }

        $source = CollectSource::find($id);
        if (!$source) {
            return $this->error('记录不存在');
        }

        $source->save([
            'name' => $name,
            'api_url' => $api_url,
            'site_type' => $site_type,
            'status' => $status,
        ]);

        return $this->success($source);
    }

    /**
     * 删除
     */
    public function delete()
    {
        $data = $this->getData();
        $id = intval($data['id'] ?? 0);

        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $source = CollectSource::find($id);
        if (!$source) {
            return $this->error('记录不存在');
        }

        $source->delete();

        return $this->success();
    }

    /**
     * 测试连接
     */
    public function test()
    {
        $data = $this->getData();
        $api_url = trim($data['api_url'] ?? '');

        if (empty($api_url)) {
            return $this->error('接口地址不能为空');
        }

        try {
            // 测试获取视频分类接口
            $testUrl = rtrim($api_url, '/') . '/api.php/provide/artlist?ac=types';
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'ignore_errors' => true,
                ]
            ]);
            
            $response = @file_get_contents($testUrl, false, $context);
            
            if ($response === false) {
                return $this->error('连接失败，请检查接口地址是否正确');
            }

            $result = json_decode($response, true);
            
            if ($result && isset($result['code']) && $result['code'] == 200) {
                return $this->success(['msg' => '连接成功']);
            } else {
                return $this->error('接口返回数据格式不正确');
            }
        } catch (\Exception $e) {
            return $this->error('连接失败：' . $e->getMessage());
        }
    }

    /**
     * 切换状态
     */
    public function toggleStatus()
    {
        $data = $this->getData();
        $id = intval($data['id'] ?? 0);

        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $source = CollectSource::find($id);
        if (!$source) {
            return $this->error('记录不存在');
        }

        $source->save(['status' => $source->status ? 0 : 1]);

        return $this->success();
    }
}
