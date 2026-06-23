<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Banner;
use app\model\Video;
use app\model\AdminLog;

class BannerController extends BaseController
{
    public function list()
    {
        try {
            $page = $this->request->param('page', 1);
            $limit = $this->request->param('limit', 20);
            
            $list = Banner::order('sort_order', 'asc')
                ->order('created_at', 'desc')
                ->page($page, $limit)
                ->select();
            
            $total = Banner::count();
            
            foreach ($list as $item) {
                if ($item->type == Banner::TYPE_VIDEO && $item->video_id > 0) {
                    $video = Video::find($item->video_id);
                    if ($video) {
                        $item->video_title = $video->title;
                        $item->cover_url = $video->cover_url;
                    }
                }
            }
            
            return $this->success([
                'list' => $list,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            // 表不存在时返回空列表
            return $this->success([
                'list' => [],
                'total' => 0
            ]);
        }
    }

    public function save()
    {
        try {
            $data = $this->getData();
            
            $id = intval($data['id'] ?? 0);
            
            if ($id > 0) {
                $banner = Banner::find($id);
                if (!$banner) {
                    return $this->error('轮播图不存在');
                }
            } else {
                $banner = new Banner();
            }
            
            // 编辑时，未传递的字段保留原值
            $type = isset($data['type']) ? intval($data['type']) : ($id > 0 ? $banner->type : Banner::TYPE_VIDEO);
            $videoId = isset($data['video_id']) ? intval($data['video_id']) : ($id > 0 ? $banner->video_id : 0);
            $title = isset($data['title']) ? trim($data['title']) : ($id > 0 ? $banner->title : '');
            $imageUrl = isset($data['image_url']) ? trim($data['image_url']) : ($id > 0 ? $banner->image_url : '');
            $linkUrl = isset($data['link_url']) ? trim($data['link_url']) : ($id > 0 ? $banner->link_url : '');
            $sortOrder = isset($data['sort_order']) ? intval($data['sort_order']) : ($id > 0 ? $banner->sort_order : 100);
            $status = isset($data['status']) ? intval($data['status']) : ($id > 0 ? $banner->status : Banner::STATUS_ENABLED);
            
            // 处理到期时间（广告类型）
            $expireAt = null;
            if ($type == Banner::TYPE_AD) {
                if (isset($data['never_expire']) && $data['never_expire']) {
                    $expireAt = null; // 永不过期
                } else if (isset($data['expire_at']) && !empty($data['expire_at'])) {
                    // 设置到期时间（自动设置为当天的23:59:59）
                    $expireAt = $data['expire_at'];
                    if (!str_contains($expireAt, '23:59:59')) {
                        // 如果只传了日期，自动补充时间
                        $expireAt = substr($expireAt, 0, 10) . ' 23:59:59';
                    }
                } else if ($id > 0) {
                    $expireAt = $banner->expire_at; // 编辑时保留原值
                }
            }
            
            // 新建时验证必填字段
            if ($id <= 0) {
                if ($type == Banner::TYPE_VIDEO && $videoId <= 0) {
                    return $this->error('请选择视频');
                }
                
                if ($type == Banner::TYPE_AD && (empty($title) || empty($imageUrl))) {
                    return $this->error('广告标题和图片不能为空');
                }
            }
            
            $banner->type = $type;
            $banner->video_id = $videoId;
            $banner->title = $title;
            $banner->image_url = $imageUrl;
            $banner->link_url = $linkUrl;
            $banner->sort_order = $sortOrder;
            $banner->status = $status;
            $banner->expire_at = $expireAt;
            $banner->save();
            
            $this->limitCount();

            $adminId = session('admin_id') ?? 0;
            $actionText = $id > 0 ? '编辑' : '添加';
            AdminLog::record($adminId, AdminLog::TYPE_OTHER, "轮播图「{$banner->title}」(ID:{$banner->id}) - {$actionText}");

            return $this->success('保存成功');
        } catch (\Exception $e) {
            return $this->error('保存失败：' . $e->getMessage());
        }
    }

    public function delete()
    {
        $id = intval($this->request->param('id', 0));

        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $banner = Banner::find($id);
        $title = $banner ? $banner->title : "ID:{$id}";
        Banner::destroy($id);

        $adminId = session('admin_id') ?? 0;
        AdminLog::record($adminId, AdminLog::TYPE_OTHER, "删除轮播图「{$title}");

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

        $adminId = session('admin_id') ?? 0;
        $actionText = $status ? '启用' : '禁用';
        AdminLog::record($adminId, AdminLog::TYPE_OTHER, "轮播图「{$banner->title}」(ID:{$id}) - {$actionText}");

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