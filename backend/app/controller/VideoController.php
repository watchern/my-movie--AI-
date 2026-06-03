<?php
namespace app\controller;

use app\BaseController;
use app\model\Video;
use app\model\Category;
use app\model\User;
use app\model\VideoSource;

/**
 * 视频控制器
 */
class VideoController extends BaseController
{
    /**
     * 首页推荐列表
     */
    public function home()
    {
        // 轮播图（最新最热影视）
        $banners = Video::where('is_show', 1)
            ->order('play_count', 'desc')
            ->order('created_at', 'desc')
            ->limit(5)
            ->select();

        // 热门电影
        $hotMovies = Video::where('is_show', 1)
            ->where('type', Video::TYPE_MOVIE)
            ->order('play_count', 'desc')
            ->limit(6)
            ->select();

        // 热门电视剧
        $hotTvs = Video::where('is_show', 1)
            ->where('type', Video::TYPE_TV)
            ->order('play_count', 'desc')
            ->limit(6)
            ->select();

        // 热门动漫
        $hotAnimes = Video::where('is_show', 1)
            ->where('type', Video::TYPE_ANIME)
            ->order('play_count', 'desc')
            ->limit(6)
            ->select();

        // 热门短视频
        $hotShorts = Video::where('is_show', 1)
            ->where('type', Video::TYPE_SHORT)
            ->order('play_count', 'desc')
            ->limit(6)
            ->select();

        return $this->success([
            'banners' => $this->formatVideoList($banners),
            'hot_movies' => $this->formatVideoList($hotMovies),
            'hot_tvs' => $this->formatVideoList($hotTvs),
            'hot_animes' => $this->formatVideoList($hotAnimes),
            'hot_shorts' => $this->formatVideoList($hotShorts),
        ]);
    }

    /**
     * 视频分类列表
     */
    public function list()
    {
        $data = $this->getData();
        $type = intval($data['type'] ?? 1);
        $categoryId = intval($data['category_id'] ?? 0);
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(50, intval($data['limit'] ?? 20)));
        $orderBy = $data['order_by'] ?? 'update_time';

        $where = [
            ['is_show', '=', 1],
            ['type', '=', $type],
        ];

        if ($categoryId > 0) {
            $where[] = ['category_id', '=', $categoryId];
        }

        $order = 'created_at desc';
        switch ($orderBy) {
            case 'hot':
                $order = 'play_count desc';
                break;
            case 'rating':
                $order = 'rating desc';
                break;
            case 'update_time':
                $order = 'updated_at desc';
                break;
        }

        $list = Video::where($where)
            ->order($order)
            ->page($page, $limit)
            ->select();

        $total = Video::where($where)->count();

        return $this->success([
            'list' => $this->formatVideoList($list),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
        ]);
    }

    /**
     * 视频详情
     */
    public function detail()
    {
        $id = intval($this->request->param('id', 0));

        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $video = Video::with(['category', 'episodes'])->find($id);

        if (!$video || $video->is_show != 1) {
            return $this->error('视频不存在');
        }

        // 增加播放次数
        $video->play_count = $video->play_count + 1;
        $video->save();

        // 检查VIP权限
        $userId = $this->request->uid ?? 0;
        $isVip = false;
        if ($video->is_vip == 1) {
            if ($userId > 0) {
                $user = User::find($userId);
                $isVip = $user && $user->isVipValid();
            }
        }

        return $this->success([
            'id' => $video->id,
            'title' => $video->title,
            'subtitle' => $video->subtitle,
            'type' => $video->type,
            'type_name' => $video->type_name,
            'cover' => $video->cover_url,
            'banner' => $video->banner,
            'director' => $video->director,
            'actors' => $video->actors_list,
            'description' => $video->description,
            'duration' => $video->duration,
            'release_year' => $video->release_year,
            'region' => $video->region,
            'language' => $video->language,
            'rating' => $video->rating,
            'play_count' => $video->play_count,
            'is_vip' => $video->is_vip,
            'is_vip_valid' => $isVip,
            'category' => $video->category ? [
                'id' => $video->category->id,
                'name' => $video->category->name,
            ] : null,
            'episodes' => $video->episodes ? $video->episodes->toArray() : [],
            'created_at' => $video->created_at,
        ]);
    }

    /**
     * 排行榜
     */
    public function rank()
    {
        $type = intval($this->request->param('type', 0)); // 0为全部
        $page = max(1, intval($this->request->param('page', 1)));
        $limit = max(1, min(50, intval($this->request->param('limit', 20))));

        $where = [['is_show', '=', 1]];
        if ($type > 0) {
            $where[] = ['type', '=', $type];
        }

        $list = Video::where($where)
            ->order('play_count', 'desc')
            ->page($page, $limit)
            ->select();

        return $this->success([
            'list' => $this->formatVideoList($list),
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 搜索
     */
    public function search()
    {
        $data = $this->getData();
        $keyword = trim($data['keyword'] ?? '');
        $type = intval($data['type'] ?? 0);
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(50, intval($data['limit'] ?? 20)));

        if (empty($keyword)) {
            return $this->error('关键词不能为空');
        }

        $where = [['is_show', '=', 1]];
        if ($type > 0) {
            $where[] = ['type', '=', $type];
        }

        // 模糊搜索标题
        $where[] = ['title', 'like', "%{$keyword}%"];

        $list = Video::where($where)
            ->order('play_count', 'desc')
            ->page($page, $limit)
            ->select();

        $total = Video::where($where)->count();

        return $this->success([
            'list' => $this->formatVideoList($list),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 获取分类列表
     */
    public function categories()
    {
        $type = intval($this->request->param('type', 0));

        $where = [];
        if ($type > 0) {
            $where[] = ['type', '=', $type];
        }

        $list = Category::where($where)
            ->order('sort_order', 'asc')
            ->select();

        return $this->success($list);
    }

    /**
     * 获取播放地址
     */
    public function playUrl()
    {
        $id = intval($this->request->param('id', 0));
        $episodeId = intval($this->request->param('episode_id', 0));

        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $video = Video::find($id);
        if (!$video || $video->is_show != 1) {
            return $this->error('视频不存在');
        }

        $playUrl = '';

        // 如果是电视剧/动漫，获取指定剧集
        if ($episodeId > 0 && in_array($video->type, [Video::TYPE_TV, Video::TYPE_ANIME])) {
            $episode = VideoSource::where('id', $episodeId)
                ->where('video_id', $id)
                ->find();
            if ($episode) {
                $playUrl = $episode->play_url;
            }
        } else {
            // 电影/短视频从 video_sources 获取第一个播放地址
            $videoSource = VideoSource::where('video_id', $id)
                ->order('sort_order', 'asc')
                ->find();
            if ($videoSource) {
                $playUrl = $videoSource->play_url;
            }
        }

        if (empty($playUrl)) {
            return $this->error('播放地址不存在');
        }

        // 检查VIP权限
        $userId = $this->request->uid ?? 0;
        $isVip = false;
        if ($video->is_vip == 1) {
            if ($userId > 0) {
                $user = User::find($userId);
                $isVip = $user && $user->isVipValid();
            }
        }

        return $this->success([
            'play_url' => $playUrl,
            'is_vip' => $video->is_vip,
            'is_vip_valid' => $isVip,
        ]);
    }

    /**
     * 格式化视频列表
     */
    private function formatVideoList($list)
    {
        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'title' => $item->title,
                'subtitle' => $item->subtitle,
                'cover_url' => $item->cover_url,
                'type' => $item->type,
                'type_name' => $item->type_name,
                'rating' => $item->rating,
                'play_count' => $item->play_count,
                'is_vip' => $item->is_vip,
                'release_year' => $item->release_year,
                'region' => $item->region,
            ];
        }
        return $result;
    }
}
