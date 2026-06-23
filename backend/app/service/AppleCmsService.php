<?php
namespace app\service;

use app\model\SourceSite;
use app\model\Video;
use app\model\Category;
use app\model\VideoSource;
use think\facade\Cache;
use think\facade\Log;

/**
 * 苹果CMS采集服务
 */
class AppleCmsService
{
    private $site;
    private $apiUrl;
    private $collectSourceId = 0;

    public function __construct(SourceSite $site, int $collectSourceId = 0)
    {
        $this->site = $site;
        $this->collectSourceId = $collectSourceId;
        $apiUrl = trim($site->api_url);

        // 兼容用户直接配置完整接口地址的情况
        // 例如：http://caiji.dyttzyapi.com/api.php/provide/vod/?ac=detail
        if (stripos($apiUrl, 'api.php/provide/vod') !== false) {
            // 去掉 ?ac=xxx 等查询参数，只保留基础接口路径
            $apiUrl = preg_replace('/\?.*$/', '', $apiUrl);
            $this->apiUrl = rtrim($apiUrl, '/');
        } else {
            $this->apiUrl = rtrim($apiUrl, '/');
        }
    }

    /**
     * 获取分类映射
     */
    public function getClassList(): array
    {
        $url = $this->apiUrl . '/api.php/provide/class';
        $data = $this->curlGet($url);

        if (empty($data) || !isset($data['code']) || $data['code'] != 1) {
            throw new \Exception('获取分类失败: ' . ($data['msg'] ?? '未知错误'));
        }

        $result = [];
        foreach ($data['list'] ?? [] as $item) {
            $result[$item['type_id']] = [
                'type_id' => $item['type_id'],
                'type_name' => $item['type_name'],
                'type_pid' => $item['type_pid'] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * 获取视频列表
     */
    /**
     * 获取视频列表，支持缓存（用于断点续采）
     */
    public function getVideoList(array $typeIds = [], int $page = 1, int $limit = 20): array
    {
        $cacheKey = '';
        if ($this->collectSourceId > 0) {
            $cacheKey = 'collection_video_list_' . $this->collectSourceId . '_' . md5(json_encode([$typeIds, $page, $limit]));
            $cached = Cache::get($cacheKey);
            if (!empty($cached['list'])) {
                return $cached;
            }
        }

        $params = [
            'ac' => 'detail',
            'pg' => $page,
            'pagesize' => $limit,
        ];

        if (!empty($typeIds)) {
            $params['type'] = implode(',', $typeIds);
        }

        $url = $this->apiUrl . '/api.php/provide/vod?' . http_build_query($params);
        // log 记录采集的url
        Log::info('采集视频列表url: ' . $url);  

        $data = $this->curlGet($url);

        // log 记录采集到的数据
        Log::info('采集视频列表数据: ' . json_encode($data));

        if (empty($data) || !isset($data['code']) || $data['code'] != 1) {
            throw new \Exception('获取视频列表失败: ' . ($data['msg'] ?? '未知错误'));
        }

        $result = [
            'total' => $data['total'] ?? 0,
            'page' => $data['page'] ?? $page,
            'pagecount' => $data['pagecount'] ?? 0,
            'limit' => $data['limit'] ?? $limit,
            'list' => $data['list'] ?? [],
        ];

        // 缓存视频列表，用于刷新页面后继续处理
        if ($cacheKey) {
            Cache::set($cacheKey, $result, 3600);
        }

        return $result;
    }

    /**
     * 获取视频详情
     */
    public function getVideoDetail(string $vodId): ?array
    {
        $url = $this->apiUrl . '/api.php/provide/vod?ac=detail&ids=' . $vodId;
        // log 记录采集的url
        Log::info('采集视频详情url: ' . $url);  

        $data = $this->curlGet($url);
        //log记录采集获取的数据
        Log::info('采集视频详情数据: ' . json_encode($data));


        if (empty($data) || !isset($data['code']) || $data['code'] != 1) {
            return null;
        }

        return $data['list'][0] ?? null;
    }

    /**
     * 搜索视频
     */
    public function search(string $keyword, int $page = 1, int $limit = 20): array
    {
        // limit 最大值100
        $limit = min($limit, 100);
        $url = $this->apiUrl . '/api.php/provide/vod?ac=detail&wd=' . urlencode($keyword) . '&pg=' . $page . '&pagesize=' . $limit;
        $data = $this->curlGet($url);

        if (empty($data) || !isset($data['code']) || $data['code'] != 1) {
            return [
                'total' => 0,
                'list' => [],
            ];
        }

        return [
            'total' => $data['total'] ?? 0,
            'page' => $data['page'] ?? $page,
            'list' => $data['list'] ?? [],
        ];
    }

    /**
     * 更新采集进度到缓存
     */
    private function updateProgress(array $data): void
    {
        if ($this->collectSourceId <= 0) {
            return;
        }

        $key = 'collection_progress_' . $this->collectSourceId;
        $progress = array_merge([
            'status' => 'running',
            'total' => 0,
            'current' => 0,
            'percent' => 0,
            'msg' => '',
            'updated_at' => time(),
        ], $data);

        Cache::set($key, $progress, 3600);
    }

    /**
     * 清除采集进度缓存
     */
    private function clearProgress(): void
    {
        if ($this->collectSourceId <= 0) {
            return;
        }

        Cache::delete('collection_progress_' . $this->collectSourceId);
    }

    /**
     * 一键采集视频到本地
     * 支持缓存视频列表和断点续采
     */
    public function collectToLocal(array $typeIds = [], int $limit = 100): array
    {
        $result = [
            'success' => 0,
            'exists' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $cacheKey = $this->collectSourceId > 0 ? 'collection_video_list_' . $this->collectSourceId . '_' . md5(json_encode([$typeIds, $limit])) : '';
        $indexKey = $this->collectSourceId > 0 ? 'collection_process_index_' . $this->collectSourceId : '';

        try {
            // 获取视频列表（会优先使用缓存）
            $listData = $this->getVideoList($typeIds, 1, $limit);
            $total = count($listData['list']);

            // 读取上次处理到的索引
            $startIndex = 0;
            if ($indexKey) {
                $startIndex = intval(Cache::get($indexKey, 0));
                if ($startIndex > 0) {
                    Log::info("[CollectionTask] 断点续采，从第 {$startIndex} 个继续处理");
                }
            }

            $this->updateProgress([
                'status' => 'running',
                'total' => $total,
                'current' => $startIndex,
                'percent' => $total > 0 ? floor(($startIndex / $total) * 100) : 0,
                'msg' => '开始采集',
            ]);

            for ($index = $startIndex; $index < $total; $index++) {
                $item = $listData['list'][$index];
                $current = $index + 1;
                $percent = $total > 0 ? floor(($current / $total) * 100) : 0;

                $this->updateProgress([
                    'status' => 'running',
                    'total' => $total,
                    'current' => $current,
                    'percent' => $percent,
                    'msg' => "正在处理第 {$current}/{$total} 个视频",
                ]);

                 try {
                     $vodId = $item['vod_id'] ?? '';
                     if (empty($vodId)) {
                         $result['failed']++;
                         $result['errors'][] = 'missing vod_id';
                         continue;
                     }

                     $saveData = $item;
                     $hasPlayUrl = !empty($item['vod_play_url']);
                     $hasDescription = !empty($item['vod_content']);
                     $hasCover = !empty($item['vod_pic']);

                     if (!$hasPlayUrl || !$hasDescription || !$hasCover) {
                         $detail = $this->getVideoDetail($vodId);
                         if (!empty($detail)) {
                             $saveData = $detail;
                         }
                     }

                     $this->saveVideo($saveData);
                     $result['success']++;
                 } catch (\Exception $e) {
                     if (strpos($e->getMessage(), '已存在') !== false) {
                         $result['exists']++;
                     } else {
                         $result['failed']++;
                         $result['errors'][] = ($item['vod_id'] ?? 'unknown') . ': ' . $e->getMessage();
                     }
                 }

                // 记录处理进度
                if ($indexKey) {
                    Cache::set($indexKey, $current, 3600);
                }
            }

            // 更新采集时间
            $this->site->last_sync_at = date('Y-m-d H:i:s');
            $this->site->save();

            $this->updateProgress([
                'status' => 'completed',
                'total' => $total,
                'current' => $total,
                'percent' => 100,
                'msg' => "采集完成，成功 {$result['success']}，已存在 {$result['exists']}，失败 {$result['failed']}",
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            $this->updateProgress([
                'status' => 'failed',
                'msg' => '采集异常: ' . $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // 完成后清除缓存和进度索引
            if ($this->collectSourceId > 0) {
                if ($cacheKey) {
                    Cache::delete($cacheKey);
                }
                if ($indexKey) {
                    Cache::delete($indexKey);
                }

                // 进度保留 5 分钟后自动清除
                Cache::set('collection_progress_' . $this->collectSourceId, [
                    'status' => $result['failed'] > 0 && $result['success'] == 0 ? 'failed' : 'completed',
                    'total' => $total ?? 0,
                    'current' => $total ?? 0,
                    'percent' => 100,
                    'msg' => "采集完成，成功 {$result['success']}，已存在 {$result['exists']}，失败 {$result['failed']}",
                    'result' => $result,
                    'updated_at' => time(),
                ], 300);
            }
        }

        return $result;
    }

    private $batchEpisodes = [];

    public function saveVideo(array $item): Video
    {
        $title = $item['vod_name'] ?? '';
        $year = $item['vod_year'] ?? '';
        $vodId = $item['vod_id'] ?? '';
        Log::debug('[saveVideo] 开始保存视频, title=' . $title . ', year=' . $year);

        $video = Video::where('title', $title)
            ->where('release_year', $year)
            ->find();

        $newEpisodeCount = $this->parseEpisodeCount($item['vod_play_url'] ?? '');

        if ($video) {
            Log::debug('[saveVideo] 视频已存在, id=' . $video->id . ', new_episodes=' . $newEpisodeCount);

            $existingCount = VideoSource::where('video_id', $video->id)
                ->where('source_site_id', $this->site->id)
                ->count();

            if ($newEpisodeCount <= $existingCount) {
                Log::debug('[saveVideo] 剧集数量未增加，跳过: existing=' . $existingCount . ', new=' . $newEpisodeCount);
                return $video;
            }

            Log::debug('[saveVideo] 剧集数量增加，更新: existing=' . $existingCount . ', new=' . $newEpisodeCount);
        } else {
            $video = new Video();
            $video->title = $title;
            $video->created_at = date('Y-m-d H:i:s');

            $categoryId = $this->getOrCreateCategory($item);
            $type = $this->getVideoType($item);

            $video->category_id = $categoryId;
            $video->type = $type;
            $video->cover = $item['vod_pic'] ?? '';
            $video->banner = $item['vod_pic_slide'] ?? '';
            $video->director = $item['vod_director'] ?? '';
            $video->actors = isset($item['vod_actor']) ? json_encode(explode(',', $item['vod_actor']), JSON_UNESCAPED_UNICODE) : '[]';
            $video->description = $item['vod_content'] ?? '';
            $video->duration = intval($item['vod_duration'] ?? 0);
            $video->release_year = $year;
            $video->region = $item['vod_area'] ?? '';
            $video->language = $item['vod_lang'] ?? '';
            $video->rating = floatval($item['vod_score'] ?? 0);
            $video->is_vip = 0;
            $video->is_show = 0;

            try {
                $video->save();
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
                    Log::info('[saveVideo] 并发重复插入，跳过: title=' . $title);
                    $video = Video::where('title', $title)->where('release_year', $year)->find();
                    if (!$video) {
                        throw $e;
                    }
                } else {
                    throw $e;
                }
            }
        }

        if (!empty($vodId)) {
            $video->source_vod_id = $vodId;
            $video->updated_at = date('Y-m-d H:i:s');
            $video->save();
        }

        if (!empty($item['vod_play_url'])) {
            $this->batchEpisodes[] = ['video_id' => $video->id, 'play_url' => $item['vod_play_url']];
        }

        return $video;
    }

    private function parseEpisodeCount(string $playUrl): int
    {
        if (empty($playUrl)) {
            return 0;
        }

        $count = 0;
        $parts = explode('$$$', $playUrl);
        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }
            $episodes = explode('#', $part);
            foreach ($episodes as $episodeStr) {
                if (empty($episodeStr)) {
                    continue;
                }
                $pos = strpos($episodeStr, '$');
                if ($pos !== false) {
                    $url = substr($episodeStr, $pos + 1);
                    if ($this->isVideoUrl($url)) {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    public function flushBatchEpisodes(): void
    {
        if (empty($this->batchEpisodes)) {
            return;
        }

        $videoIds = array_column($this->batchEpisodes, 'video_id');
        VideoSource::whereIn('video_id', $videoIds)
            ->where('source_site_id', $this->site->id)
            ->delete();

        foreach ($this->batchEpisodes as $batch) {
            $this->saveEpisodesById($batch['video_id'], $batch['play_url']);
        }

        $this->batchEpisodes = [];
    }

    private function saveEpisodesById(int $videoId, string $playUrl): void
    {
        $parts = explode('$$$', $playUrl);

        foreach ($parts as $index => $part) {
            if (empty($part)) {
                continue;
            }

            $episodes = explode('#', $part);

            foreach ($episodes as $episodeStr) {
                if (empty($episodeStr)) {
                    continue;
                }

                $pos = strpos($episodeStr, '$');
                if ($pos !== false) {
                    $episodeName = trim(substr($episodeStr, 0, $pos));
                    $url = substr($episodeStr, $pos + 1);

                    if (!$this->isVideoUrl($url)) {
                        continue;
                    }

                    $episode = new VideoSource();
                    $episode->video_id = $videoId;
                    $episode->source_site_id = $this->site->id;
                    $episode->name = $episodeName;
                    $episode->play_url = $url;
                    $episode->sort_order = $index;
                    $episode->status = 1;
                    $episode->save();
                }
            }
        }
    }

    /**
     * 获取或创建分类
     */
    private function getOrCreateCategory(array $item): int
    {
        $typeName = $item['type_name'] ?? '';
        $typeId = $item['type_id'] ?? 0;

        // 查询本地分类
        $category = Category::where('name', $typeName)->find();

        if ($category) {
            return $category->id;
        }

        // 确定分类类型
        $type = Video::TYPE_MOVIE;
        $typeNameLower = mb_strtolower($typeName);
        if (strpos($typeNameLower, '剧') !== false) {
            $type = Video::TYPE_TV;
        } elseif (strpos($typeNameLower, '动漫') !== false || strpos($typeNameLower, '动画') !== false) {
            $type = Video::TYPE_ANIME;
        } elseif (strpos($typeNameLower, '短') !== false || strpos($typeNameLower, '综艺') !== false) {
            $type = Video::TYPE_SHORT;
        }

        // 创建分类
        $category = new Category();
        $category->name = $typeName;
        $category->slug = pinyin($typeName);
        $category->type = $type;
        $category->sort_order = 100;
        $category->save();

        return $category->id;
    }

    /**
     * 确定视频类型
     */
    private function getVideoType(array $item): int
    {
        $typeName = $item['type_name'] ?? '';
        $typeNameLower = mb_strtolower($typeName);

        if (strpos($typeNameLower, '动漫') !== false || strpos($typeNameLower, '动画') !== false) {
            return Video::TYPE_ANIME;
        } elseif (strpos($typeNameLower, '短') !== false || strpos($typeNameLower, '综艺') !== false) {
            return Video::TYPE_SHORT;
        } elseif (strpos($typeNameLower, '剧') !== false) {
            return Video::TYPE_TV;
        }

        return Video::TYPE_MOVIE;
    }

    /**
     * 解析播放地址
     * 兼容苹果CMS格式：$$$分隔播放器源，#分隔剧集，$分隔集名和URL
     */
    private function parsePlayUrl(array $item): array
    {
        $playUrl = $item['vod_play_url'] ?? '';
        $playFrom = $item['vod_play_from'] ?? '';

        if (empty($playUrl)) {
            return [];
        }

        $urls = [];
        // 先按 $$$ 分隔不同播放器源
        $sourceParts = explode('$$$', $playUrl);
        $sourceNames = explode('$$$', $playFrom);

        foreach ($sourceParts as $sourceIndex => $sourceUrlStr) {
            if (empty($sourceUrlStr)) {
                continue;
            }

            // 再按 # 分隔剧集
            $episodes = explode('#', $sourceUrlStr);
            $sourceName = $sourceNames[$sourceIndex] ?? ('source' . ($sourceIndex + 1));

            foreach ($episodes as $episodeStr) {
                if (empty($episodeStr)) {
                    continue;
                }
                $pos = strpos($episodeStr, '$');
                if ($pos !== false) {
                    $name = substr($episodeStr, 0, $pos);
                    $url = substr($episodeStr, $pos + 1);
                    $urls[] = [
                        'player' => $sourceName,
                        'name' => $name,
                        'url' => $url,
                    ];
                }
            }
        }

        return $urls;
    }

    /**
     * 保存剧集
     * 兼容苹果CMS格式：$$$分隔播放器源，#分隔剧集，$分隔集名和URL
     * 只保存视频类型的URL，丢弃分享页、云盘链接等非视频地址
     */
    private function saveEpisodes(Video $video, string $playUrl): void
    {
        $parts = explode('$$$', $playUrl);

        foreach ($parts as $index => $part) {
            if (empty($part)) {
                continue;
            }

            $episodes = explode('#', $part);

            foreach ($episodes as $episodeStr) {
                if (empty($episodeStr)) {
                    continue;
                }

                $pos = strpos($episodeStr, '$');
                if ($pos !== false) {
                    $episodeName = trim(substr($episodeStr, 0, $pos));
                    $url = substr($episodeStr, $pos + 1);

                    // 跳过非视频类型的播放地址
                    if (!$this->isVideoUrl($url)) {
                        continue;
                    }

                    $episode = new VideoSource();
                    $episode->video_id = $video->id;
                    $episode->source_site_id = $this->site->id;
                    $episode->name = $episodeName;
                    $episode->play_url = $url;
                    $episode->sort_order = $index;
                    $episode->status = 1;
                    $episode->save();
                }
            }
        }
    }

    /**
     * 判断URL是否为可直接播放的视频地址
     */
    private function isVideoUrl(string $url): bool
    {
        $url = trim($url);
        if (empty($url)) {
            return false;
        }

        // 移除URL中的查询参数，获取路径部分用于判断后缀
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // 支持的视频格式
        $videoExts = ['m3u8', 'mp4', 'flv', 'ts', 'webm', 'ogg', 'mov', 'mkv', 'avi', 'rmvb', 'wmv'];

        // 检查后缀
        if (in_array($ext, $videoExts)) {
            return true;
        }

        // 如果路径没有明显后缀，但包含某些流媒体协议或特征，也认为是视频
        if (strpos($url, '.m3u8') !== false || strpos($url, '.mp4') !== false) {
            return true;
        }

        return false;
    }

    /**
     * 同步更新视频
     */
    public function updateVideo(Video $video): Video
    {
        // 视频表没有 source_id 字段，此方法暂不实现
        return $video;
    }

    /**
     * GET请求
     */
    private function curlGet(string $url, int $timeout = 30): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('CURL错误: ' . $error);
        }

        // 尝试解析JSON
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // 如果不是JSON，尝试XML解析（苹果CMS旧版本）
            $data = $this->parseXml($response);
        }

        return $data ?? [];
    }

    /**
     * 解析XML（兼容旧版苹果CMS）
     */
    private function parseXml(string $xml): array
    {
        $data = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$data) {
            return [];
        }

        $result = [
            'code' => 1,
            'msg' => 'success',
            'list' => [],
        ];

        if (isset($data->list->video)) {
            foreach ($data->list->video as $video) {
                $item = [];
                foreach ($video as $key => $value) {
                    $item[(string)$key] = (string)$value;
                }
                $result['list'][] = $item;
            }
        }

        return $result;
    }
}
