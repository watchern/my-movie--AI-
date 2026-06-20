<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Video;
use app\model\Category;
use app\model\User;
use app\model\SourceSite;
use app\model\CardKey;
use app\model\VideoSource;
use app\service\AppleCmsService;
use app\service\CollectionTaskService;

/**
 * 管理端 - 视频管理
 */
class VideoController extends BaseController
{
    /**
     * 视频列表
     */
    public function list()
    {
        $data = $this->getData();
        $keyword = trim($data['keyword'] ?? '');
        $type = intval($data['type'] ?? 0);
        $isVip = isset($data['is_vip']) ? intval($data['is_vip']) : -1;
        $isShow = $data['is_show'] ?? '';
        $page = max(1, intval($data['page'] ?? 1));
        $limit = max(1, min(100, intval($data['limit'] ?? 20)));

        $where = [];
        if (!empty($keyword)) {
            $where[] = ['title', 'like', "%{$keyword}%"];
        }
        if ($type > 0) {
            $where[] = ['type', '=', $type];
        }
        if ($isVip >= 0) {
            $where[] = ['is_vip', '=', $isVip];
        }
        if ($isShow !== '' && in_array($isShow, [0, 1])) {
            $where[] = ['is_show', '=', $isShow];
        }

        $list = Video::with(['category'])
            ->where($where)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select();

        $total = Video::where($where)->count();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item->id,
                'title' => $item->title,
                'type' => $item->type,
                'type_name' => $item->type_name,
                'category_id' => $item->category_id,
                'tags' => $item->tags,
                'cover' => $item->cover,
                'banner' => $item->banner,
                'director' => $item->director,
                'actors' => $item->actors,
                'description' => $item->description,
                'duration' => $item->duration,
                'release_year' => $item->release_year,
                'region' => $item->region,
                'language' => $item->language,
                'rating' => $item->rating,
                'play_count' => $item->play_count,
                'is_vip' => $item->is_vip,
                'is_show' => $item->is_show,
                'category' => $item->category ? $item->category->name : '',
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

    /**
     * 添加/编辑视频
     */
    public function save()
    {
        $data = $this->getData();
        $id = intval($data['id'] ?? 0);

        if (empty($data['title'])) {
            return $this->error('标题不能为空');
        }

        if ($id > 0) {
            $video = Video::find($id);
            if (!$video) {
                return $this->error('视频不存在');
            }
        } else {
            $video = new Video();
        }

        $video->title = trim($data['title']);
        $video->type = intval($data['type'] ?? Video::TYPE_MOVIE);
        $video->category_id = intval($data['category_id'] ?? 0);
        $video->tags = is_array($data['tags'] ?? '') ? json_encode($data['tags'], JSON_UNESCAPED_UNICODE) : ($data['tags'] ?? '[]');
        $video->cover = trim($data['cover'] ?? '');
        $video->banner = trim($data['banner'] ?? '');
        $video->director = trim($data['director'] ?? '');
        $video->actors = is_array($data['actors'] ?? '') ? json_encode($data['actors'], JSON_UNESCAPED_UNICODE) : ($data['actors'] ?? '[]');
        $video->description = trim($data['description'] ?? '');
        $video->duration = intval($data['duration'] ?? 0);
        $video->release_year = trim($data['release_year'] ?? '');
        $video->region = trim($data['region'] ?? '');
        $video->language = trim($data['language'] ?? '');
        $video->rating = floatval($data['rating'] ?? 0);
        $video->play_count = intval($data['play_count'] ?? 0);
        $video->is_vip = intval($data['is_vip'] ?? 0);
        $video->is_show = intval($data['is_show'] ?? 1);

        $video->save();

        return $this->success(['id' => $video->id], '保存成功');
    }

    /**
     * 删除视频
     */
    public function delete()
    {
        $data = $this->getData();
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return $this->error('请选择要删除的视频');
        }

        // 确保是数组
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        // 先删除关联的剧集
        VideoSource::whereIn('video_id', $ids)->delete();

        // 再删除视频
        Video::whereIn('id', $ids)->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 快捷更新视频状态
     */
    public function updateStatus()
    {
        $data = $this->getData();
        $id = intval($data['id'] ?? 0);
        $field = $data['field'] ?? '';
        $value = intval($data['value'] ?? 0);

        if ($id <= 0) {
            return $this->error('参数错误');
        }

        if (!in_array($field, ['is_vip', 'is_show'])) {
            return $this->error('不支持的字段');
        }

        $video = Video::find($id);
        if (!$video) {
            return $this->error('视频不存在');
        }

        $video->$field = $value;
        $video->save();

        return $this->success(null, '更新成功');
    }

    /**
     * 分类列表
     */
    public function categories()
    {
        $list = Category::order('sort_order', 'asc')->select();

        return $this->success($list);
    }

    /**
     * 添加/编辑分类
     */
    public function saveCategory()
    {
        $data = $this->getData();
        $id = intval($data['id'] ?? 0);

        if (empty($data['name'])) {
            return $this->error('分类名称不能为空');
        }

        $name = trim($data['name']);
        $type = intval($data['type'] ?? 1);

        // 检查分类名称是否重复（同类型下）
        $existingCategory = Category::where('name', $name)
            ->where('type', $type)
            ->where('id', '<>', $id)
            ->find();

        if ($existingCategory) {
            return $this->error('该类型下已存在同名分类');
        }

        if ($id > 0) {
            $category = Category::find($id);
            if (!$category) {
                return $this->error('分类不存在');
            }
        } else {
            $category = new Category();
        }

        $category->name = $name;
        $category->slug = trim($data['slug'] ?? pinyin($data['name']));
        $category->type = $type;
        $category->parent_id = intval($data['parent_id'] ?? 0);
        $category->sort_order = intval($data['sort_order'] ?? 100);

        $category->save();

        return $this->success(['id' => $category->id], '保存成功');
    }

    /**
     * 删除分类
     */
    public function deleteCategory()
    {
        $data = $this->getData();
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return $this->error('请选择要删除的分类');
        }

        // 检查分类下是否有视频
        foreach ($ids as $id) {
            $count = Video::where('category_id', $id)->count();
            if ($count > 0) {
                return $this->error('分类下存在视频，无法删除');
            }
        }

        Category::whereIn('id', $ids)->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 资源站点列表
     */
    public function sourceSites()
    {
        $list = SourceSite::order('sort_order', 'asc')->select();

        return $this->success($list);
    }

    /**
     * 添加/编辑资源站点
     */
    public function saveSourceSite()
    {
        $data = $this->getData();
        $id = intval($data['id'] ?? 0);

        if (empty($data['name']) || empty($data['api_url'])) {
            return $this->error('站点名称和API地址不能为空');
        }

        if ($id > 0) {
            $site = SourceSite::find($id);
            if (!$site) {
                return $this->error('站点不存在');
            }
        } else {
            $site = new SourceSite();
        }

        $site->name = trim($data['name']);
        $site->description = trim($data['description'] ?? '');
        $site->code = trim($data['code'] ?? pinyin($data['name']));
        $site->api_url = trim($data['api_url']);
        $site->api_key = trim($data['api_key'] ?? '');
        $site->status = intval($data['status'] ?? 1);
        $site->sort_order = intval($data['sort_order'] ?? 100);

        $site->save();

        return $this->success(['id' => $site->id], '保存成功');
    }

    /**
     * 采集视频（异步触发）
     * 根据 collect_sources 配置中的 source_id 启动采集任务
     */
    public function collect()
    {
        $data = $this->getData();
        $sourceId = intval($data['source_id'] ?? 0);
        $typeIds = $data['type_ids'] ?? [];
        $limit = intval($data['limit'] ?? 100);

        if ($sourceId <= 0) {
            return $this->error('请选择资源站点');
        }

        $result = CollectionTaskService::triggerBySourceId($sourceId, $limit, $typeIds);

        if ($result['started']) {
            return $this->success($result, '采集任务已启动');
        }

        return $this->error($result['msg']);
    }

    /**
     * 获取采集进度
     */
    public function collectProgress()
    {
        $sourceId = intval($this->request->get('source_id', 0));
        if ($sourceId <= 0) {
            return $this->error('参数错误');
        }

        $progress = CollectionTaskService::getProgress($sourceId);
        return $this->success($progress);
    }

    /**
     * 获取视频剧集列表
     */
    public function episodes()
    {
        $videoId = intval($this->request->get('video_id', 0));
        if ($videoId <= 0) {
            return $this->error('参数错误');
        }

        $list = VideoSource::where('video_id', $videoId)
            ->order('sort_order', 'asc')
            ->select();

        // 关联资源站点名称
        $result = [];
        foreach ($list as $item) {
            $siteName = '';
            if ($item->source_site_id > 0) {
                $site = SourceSite::find($item->source_site_id);
                $siteName = $site ? $site->name : '';
            }

            $result[] = [
                'id' => $item->id,
                'video_id' => $item->video_id,
                'source_site_id' => $item->source_site_id,
                'source_site_name' => $siteName,
                'name' => $item->name,
                'play_url' => $item->play_url,
                'sort_order' => $item->sort_order,
                'status' => $item->status,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        }

        return $this->success($result);
    }

    /**
     * 保存剧集（添加/编辑）
     */
    public function saveEpisode()
    {
        $data = $this->getData();
        $id = intval($data['id'] ?? 0);
        $videoId = intval($data['video_id'] ?? 0);

        if ($videoId <= 0) {
            return $this->error('视频ID不能为空');
        }
        if (empty($data['name'])) {
            return $this->error('剧集名称不能为空');
        }
        if (empty($data['play_url'])) {
            return $this->error('播放地址不能为空');
        }

        if ($id > 0) {
            $episode = VideoSource::where('id', $id)
                ->where('video_id', $videoId)
                ->find();
            if (!$episode) {
                return $this->error('剧集不存在');
            }
        } else {
            $episode = new VideoSource();
            $episode->video_id = $videoId;
        }

        $episode->name = trim($data['name']);
        $episode->play_url = trim($data['play_url']);
        $episode->sort_order = intval($data['sort_order'] ?? 0);
        $episode->status = isset($data['status']) ? intval($data['status']) : 1;
        $episode->save();

        return $this->success(['id' => $episode->id], '保存成功');
    }

    /**
     * 删除剧集
     */
    public function deleteEpisode()
    {
        $data = $this->getData();
        $id = intval($data['id'] ?? 0);
        $videoId = intval($data['video_id'] ?? 0);

        if ($id <= 0 || $videoId <= 0) {
            return $this->error('参数错误');
        }

        VideoSource::where('id', $id)
            ->where('video_id', $videoId)
            ->delete();

        return $this->success(null, '删除成功');
    }
}
