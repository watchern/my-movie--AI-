<?php
namespace app\service;

use app\model\SourceSite;
use app\model\Video;
use app\model\Category;
use app\model\VideoSource;
use think\facade\Cache;

/**
 * 苹果CMS采集服务
 */
class AppleCmsService
{
    private $site;
    private $apiUrl;

    public function __construct(SourceSite $site)
    {
        $this->site = $site;
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
    public function getVideoList(array $typeIds = [], int $page = 1, int $limit = 20): array
    {
        $params = [
            'ac' => 'list',
            'page' => $page,
            'limit' => $limit,
        ];

        if (!empty($typeIds)) {
            $params['type'] = implode(',', $typeIds);
        }

        $url = $this->apiUrl . '/api.php/provide/vod?' . http_build_query($params);
        $data = $this->curlGet($url);

        if (empty($data) || !isset($data['code']) || $data['code'] != 1) {
            throw new \Exception('获取视频列表失败: ' . ($data['msg'] ?? '未知错误'));
        }

        return [
            'total' => $data['total'] ?? 0,
            'page' => $data['page'] ?? $page,
            'limit' => $data['limit'] ?? $limit,
            'list' => $data['list'] ?? [],
        ];
    }

    /**
     * 获取视频详情
     */
    public function getVideoDetail(string $vodId): ?array
    {
        $url = $this->apiUrl . '/api.php/provide/vod?ac=detail&id=' . $vodId;
        $data = $this->curlGet($url);

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
        $url = $this->apiUrl . '/api.php/provide/vod?ac=detail&wd=' . urlencode($keyword) . '&page=' . $page . '&limit=' . $limit;
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
     * 一键采集视频到本地
     */
    public function collectToLocal(array $typeIds = [], int $limit = 100): array
    {
        $result = [
            'success' => 0,
            'exists' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // 获取视频列表
        $listData = $this->getVideoList($typeIds, 1, $limit);

        foreach ($listData['list'] as $item) {
            try {
                $vodId = $item['vod_id'] ?? '';
                if (empty($vodId)) {
                    $result['failed']++;
                    $result['errors'][] = 'missing vod_id';
                    continue;
                }

                // 通过详情接口获取完整数据
                $detail = $this->getVideoDetail($vodId);
                if (empty($detail)) {
                    $result['failed']++;
                    $result['errors'][] = $vodId . ': 获取详情失败';
                    continue;
                }

                $this->saveVideo($detail);
                $result['success']++;
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), '已存在') !== false) {
                    $result['exists']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = ($item['vod_id'] ?? 'unknown') . ': ' . $e->getMessage();
                }
            }
        }

        // 更新采集时间
        $this->site->last_sync_at = date('Y-m-d H:i:s');
        $this->site->save();

        return $result;
    }

    /**
     * 保存视频到数据库
     */
    public function saveVideo(array $item): Video
    {
        // 检查是否已存在（通过标题简单检查）
        $video = Video::where('title', $item['vod_name'])
            ->find();

        if ($video) {
            throw new \Exception('视频已存在');
        }

        // 获取分类ID
        $categoryId = $this->getOrCreateCategory($item);

        // 确定视频类型
        $type = $this->getVideoType($item);

        // 处理播放地址
        $playUrls = $this->parsePlayUrl($item);

        // 创建视频记录
        $video = new Video();
        $video->title = $item['vod_name'] ?? '';
        $video->category_id = $categoryId;
        $video->type = $type;
        $video->cover = $item['vod_pic'] ?? '';
        $video->banner = $item['vod_pic_slide'] ?? '';
        $video->director = $item['vod_director'] ?? '';
        $video->actors = isset($item['vod_actor']) ? json_encode(explode(',', $item['vod_actor']), JSON_UNESCAPED_UNICODE) : '[]';
        $video->description = $item['vod_content'] ?? '';
        $video->duration = intval($item['vod_duration'] ?? 0);
        $video->release_year = $item['vod_year'] ?? '';
        $video->region = $item['vod_area'] ?? '';
        $video->language = $item['vod_lang'] ?? '';
        $video->rating = floatval($item['vod_score'] ?? 0);
        $video->is_vip = 0; // 默认非VIP
        $video->is_show = 1;

        $video->save();

        // 保存播放源/剧集（电影也可能有播放地址）
        if (!empty($item['vod_play_url'])) {
            $this->saveEpisodes($video, $item['vod_play_url']);
        }

        return $video;
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
