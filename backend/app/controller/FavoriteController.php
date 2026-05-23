<?php
namespace app\controller;

use app\BaseController;
use app\model\Favorite;
use app\model\Video;

/**
 * 收藏控制器
 */
class FavoriteController extends BaseController
{
    /**
     * 获取收藏列表
     */
    public function list()
    {
        $userId = $this->request->uid ?? 0;
        list($page, $limit) = $this->getPageParams();

        $list = Favorite::with(['video'])
            ->where('user_id', $userId)
            ->order('created_at', 'desc')
            ->page($page, $limit)
            ->select();

        $total = Favorite::where('user_id', $userId)->count();

        $result = [];
        foreach ($list as $item) {
            if ($item->video && $item->video->is_show == 1) {
                $result[] = [
                    'id' => $item->id,
                    'video_id' => $item->video->id,
                    'title' => $item->video->title,
                    'cover' => $item->video->cover_url,
                    'type' => $item->video->type,
                    'type_name' => $item->video->type_name,
                    'rating' => $item->video->rating,
                    'is_vip' => $item->video->is_vip,
                    'created_at' => $item->created_at,
                ];
            }
        }

        return $this->success([
            'list' => $result,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 添加收藏
     */
    public function add()
    {
        $userId = $this->request->uid ?? 0;
        $data = $this->getData();
        $videoId = intval($data['video_id'] ?? 0);

        if ($videoId <= 0) {
            return $this->error('参数错误');
        }

        // 检查视频是否存在
        $video = Video::find($videoId);
        if (!$video || $video->is_show != 1) {
            return $this->error('视频不存在');
        }

        // 检查是否已收藏
        $exist = Favorite::where('user_id', $userId)
            ->where('video_id', $videoId)
            ->find();
        if ($exist) {
            return $this->error('已收藏');
        }

        $favorite = new Favorite();
        $favorite->user_id = $userId;
        $favorite->video_id = $videoId;
        $favorite->save();

        return $this->success(null, '收藏成功');
    }

    /**
     * 取消收藏
     */
    public function remove()
    {
        $userId = $this->request->uid ?? 0;
        $data = $this->getData();
        $videoId = intval($data['video_id'] ?? 0);

        if ($videoId <= 0) {
            return $this->error('参数错误');
        }

        Favorite::where('user_id', $userId)
            ->where('video_id', $videoId)
            ->delete();

        return $this->success(null, '已取消收藏');
    }

    /**
     * 检查是否收藏
     */
    public function check()
    {
        $userId = $this->request->uid ?? 0;
        $data = $this->getData();
        $videoId = intval($data['video_id'] ?? 0);

        $exist = Favorite::where('user_id', $userId)
            ->where('video_id', $videoId)
            ->find();

        return $this->success([
            'is_favorite' => $exist ? true : false,
        ]);
    }
}
