<?php
namespace app;

use think\App;
use think\exception\ValidateException;
use think\Validate;

abstract class BaseController
{
    protected $request;
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        $this->initialize();
    }

    protected function initialize()
    {
    }

    /**
     * 返回成功JSON
     */
    protected function success($data = null, $msg = 'success', $code = 200)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

    /**
     * 返回错误JSON
     */
    protected function error($msg = 'error', $code = 400, $data = null)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

    /**
     * 获取请求数据
     */
    protected function getData()
    {
        return $this->request->post();
    }

    /**
     * 获取分页参数
     */
    protected function getPageParams()
    {
        $page = max(1, intval($this->request->param('page', 1)));
        $limit = max(1, min(100, intval($this->request->param('limit', 20))));
        return [$page, $limit];
    }
}
