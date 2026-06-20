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
        $description = trim($data['description'] ?? '');
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
            'description' => $description,
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
        $description = trim($data['description'] ?? '');
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
            'description' => $description,
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
     * 使用 ac=detail 接口测试苹果CMS资源站是否可用
     */
    public function test()
    {
        $data = $this->getData();
        $api_url = trim($data['api_url'] ?? '');

        if (empty($api_url)) {
            return $this->error('接口地址不能为空');
        }

        try {
            // 用户提供的接口地址已经是完整地址，例如：
            // http://caiji.dyttzyapi.com/api.php/provide/vod/?ac=detail
            // 直接请求该地址即可
            $testUrl = $api_url;

            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'ignore_errors' => true,
                    'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);

            $response = @file_get_contents($testUrl, false, $context);

            if ($response === false) {
                return $this->error('连接失败，请检查接口地址是否正确');
            }

            $result = json_decode($response, true);

            // 苹果CMS标准返回：code=1 表示成功
            if ($result && isset($result['code']) && $result['code'] == 1) {
                $listCount = isset($result['list']) ? count($result['list']) : 0;
                return $this->success([
                    'msg' => '连接成功',
                    'list_count' => $listCount,
                ]);
            } else {
                $msg = $result['msg'] ?? '接口返回数据格式不正确';
                return $this->error('接口返回异常：' . $msg);
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
