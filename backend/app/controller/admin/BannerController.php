<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Banner;
use app\model\Video;

class BannerController extends BaseController
{
    public function list()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 20);
        
        $list = Banner::order('sort_order', 'asc')
            ->order('created_at', 'desc')
            ->page($page, $limit)
            ->select();
        
        $total = Banner::count();
        
        foreach ($list as $item) {
            if ($item->type == Banner::TYPE_VIDEO && $item->video) {
                $item->video_title = $item->video->title;
                $item->cover_url = $item->video->cover_url;
            }
        }
        
        return $this->success([
            'list' => $list,
            'total' => $total
        ]);
    }

    public function save()
    {
        $data = $this->getData();
        
        $id = intval($data['id'] ?? 0);
        $type = intval($data['type'] ?? Banner::TYPE_VIDEO);
        $videoId = intval($data['video_id'] ?? 0);
        $title = trim($data['title'] ?? '');
        $imageUrl = trim($data['image_url'] ?? '');
        $linkUrl = trim($data['link_url'] ?? '');
        $sortOrder = intval($data['sort_order'] ?? 100);
        $status = intval($data['status'] ?? Banner::STATUS_ENABLED);
        
        if ($type == Banner::TYPE_VIDEO && $videoId <= 0) {
            return $this->error('请选择视频');
        }
        
        if ($type == Banner::TYPE_AD && (empty($title) || empty($imageUrl))) {
            return $this->error('广告标题和图片不能为空');
        }
        
        if ($id > 0) {
            $banner = Banner::find($id);
            if (!$banner) {
                return $this->error('轮播图不存在');
            }
        } else {
            $banner = new Banner();
        }
        
        $banner->type = $type;
        $banner->video_id = $videoId;
        $banner->title = $title;
        $banner->image_url = $imageUrl;
        $banner->link_url = $linkUrl;
        $banner->sort_order = $sortOrder;
        $banner->status = $status;
        $banner->save();
        
        $this->limitCount();
        
        return $this->success('保存成功');
    }

    public function delete()
    {
        $id = intval($this->request->param('id', 0));
        
        if ($id <= 0) {
            return $this->error('参数错误');
        }
        
        Banner::destroy($id);
        
        return $this->success('删除成功');
    }

    public function updateStatus()
    {
        $id = intval($this->request->param('id', 0));
        $status = intval($this->request->param('status', 0));
        
        if ($id <= 0) {
            return $this->error('参数错误');
        }
        
        $banner = Banner::find($id);
        if (!$banner) {
            return $this->error('轮播图不存在');
        }
        
        $banner->status = $status;
        $banner->save();
        
        return $this->success('更新成功');
    }

    public function up($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return $this->error('轮播图不存在');
        }
        
        $prev = Banner::where('sort_order', '<', $banner->sort_order)
            ->order('sort_order', 'desc')
            ->find();
        
        if ($prev) {
            $tmp = $banner->sort_order;
            $banner->sort_order = $prev->sort_order;
            $prev->sort_order = $tmp;
            $banner->save();
            $prev->save();
        }
        
        return $this->success('操作成功');
    }

    public function down($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return $this->error('轮播图不存在');
        }
        
        $next = Banner::where('sort_order', '>', $banner->sort_order)
            ->order('sort_order', 'asc')
            ->find();
        
        if ($next) {
            $tmp = $banner->sort_order;
            $banner->sort_order = $next->sort_order;
            $next->sort_order = $tmp;
            $banner->save();
            $next->save();
        }
        
        return $this->success('操作成功');
    }

    public function getVideoOptions()
    {
        $list = Video::where('is_show', 1)
            ->order('play_count', 'desc')
            ->limit(50)
            ->select();
        
        return $this->success($list);
    }

    private function limitCount($max = 5)
    {
        $count = Banner::where('status', Banner::STATUS_ENABLED)->count();
        
        if ($count > $max) {
            $excess = Banner::where('status', Banner::STATUS_ENABLED)
                ->order('sort_order', 'asc')
                ->offset($max)
                ->select();
            
            foreach ($excess as $item) {
                $item->status = Banner::STATUS_DISABLED;
                $item->save();
            }
        }
    }
}