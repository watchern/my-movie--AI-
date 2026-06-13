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
        // 轮播图（从banners表获取）
        $banners = $this->getBanners();

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
            'banners' => $banners,
            'hot_movies' => $this->formatVideoList($hotMovies),
            'hot_tvs' => $this->formatVideoList($hotTvs),
            'hot_animes' => $this->formatVideoList($hotAnimes),
            'hot_shorts' => $this->formatVideoList($hotShorts),
        ]);
    }

    /**
     * 获取轮播图数据
     */
    private function getBanners()
    {
        // 首先尝试从banners表获取
        if (class_exists('app\model\Banner')) {
            try {
                $bannerList = \app\model\Banner::where('status', 1)
                    ->order('sort_order', 'asc')
                    ->limit(5)
                    ->select();
                
                if ($bannerList && count($bannerList) > 0) {
                    $result = [];
                    $now = date('Y-m-d H:i:s');
                    foreach ($bannerList as $banner) {
                        if ($banner->type == 1 && $banner->video) {
                            // 视频类型
                            $result[] = [
                                'id' => $banner->video->id,
                                'title' => $banner->video->title,
                                'cover_url' => $banner->video->cover_url,
                                'type' => 'video',
                                'link_url' => null
                            ];
                        } else if ($banner->type == 2) {
                            // 广告类型 - 检查是否过期
                            if ($banner->expire_at && $banner->expire_at < $now) {
                                continue; // 已过期，跳过
                            }
                            $result[] = [
                                'id' => $banner->id,
                                'title' => $banner->title,
                                'cover_url' => $banner->image_url,
                                'type' => 'ad',
                                'link_url' => $banner->link_url
                            ];
                        }
                    }
                    return $result;
                }
            } catch (\Exception $e) {
                // 表不存在时回退到旧逻辑
            }
        }

        // 回退：从视频表获取最新最热的5个视频
        $banners = Video::where('is_show', 1)
            ->order('play_count', 'desc')
            ->order('created_at', 'desc')
            ->limit(5)
            ->select();
        
        $result = [];
        foreach ($banners as $banner) {
            $result[] = [
                'id' => $banner->id,
                'title' => $banner->title,
                'cover_url' => $banner->cover_url,
                'type' => 'video',
                'link_url' => null
            ];
        }
        return $result;
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
        $episodeId = intval($this->request->param('episode_id', 0));

        if ($id <= 0 && $episodeId <= 0) {
            return $this->error('参数错误');
        }

        $video = null;
        if ($episodeId > 0) {
            // 通过剧集找视频
            $source = VideoSource::find($episodeId);
            if ($source) {
                $video = Video::with(['category', 'episodes'])->find($source->video_id);
            }
        }
        if (!$video && $id > 0) {
            // 直接通过视频ID找
            $video = Video::with(['category', 'episodes'])->find($id);
        }

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
            'current_episode_id' => $episodeId > 0 ? $episodeId : null,
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

        // 热播榜（按播放量）
        $hotList = Video::where($where)
            ->order('play_count', 'desc')
            ->page($page, $limit)
            ->select();

        // 新上线（按更新时间）
        $newList = Video::where($where)
            ->order('updated_at', 'desc')
            ->page($page, $limit)
            ->select();

        return $this->success([
            'list' => $this->formatVideoList($hotList),
            'new' => $this->formatVideoList($newList),
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

        // 多字段模糊搜索：标题、副标题、导演、演员、年份、简介、标签
        $searchFields = ['title', 'subtitle', 'director', 'actors', 'release_year', 'description', 'tags'];
        
        // 构建OR条件组
        $searchWhere = function ($query) use ($keyword, $searchFields) {
            foreach ($searchFields as $field) {
                $query->whereOr($field, 'like', "%{$keyword}%");
            }
        };

        $list = Video::where($where)
            ->where($searchWhere)
            ->order('play_count', 'desc')
            ->page($page, $limit)
            ->select();

        $total = Video::where($where)
            ->where($searchWhere)
            ->count();

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
     * 播放页面数据
     */
    public function play()
    {
        $id = intval($this->request->param('id', 0));
        
        if ($id <= 0) {
            return $this->error('参数错误');
        }

        // 首先尝试通过id查找video_source（剧集模式）
        $videoSource = VideoSource::find($id);
        $video = null;
        
        if ($videoSource) {
            // 剧集模式：通过video_source找到video
            $video = Video::with(['category', 'episodes'])->find($videoSource->video_id);
        } else {
            // 电影模式：通过id查找video，取第一个video_source
            $video = Video::with(['category', 'episodes'])->find($id);
            if ($video) {
                $videoSource = VideoSource::where('video_id', $video->id)
                    ->order('sort_order', 'asc')
                    ->find();
            }
        }

        if (!$video || $video->is_show != 1) {
            return $this->error('视频不存在');
        }

        if (!$videoSource) {
            return $this->error('播放源不存在');
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
            'detail' => [
                'id' => $video->id,
                'title' => $video->title,
                'subtitle' => $video->subtitle,
                'type' => $video->type,
                'type_name' => $video->type_name,
                'cover_url' => $video->cover_url,
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
            ],
            'episode' => $videoSource->toArray(),
            'episodes' => $video->episodes ? $video->episodes->toArray() : [],
            'play_url' => $videoSource->play_url,
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
