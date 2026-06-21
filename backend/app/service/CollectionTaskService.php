<?php

namespace app\service;

use app\model\CollectSource;
use app\model\SourceSite;
use think\facade\Cache;
use think\facade\Log;

/**
 * 异步采集任务服务
 * 采用前端轮询驱动模式：trigger 只缓存视频列表，processNext 每次处理一个视频
 * 避免后台 worker 在 Windows/共享主机环境下无法启动的问题
 */
class CollectionTaskService
{
    // 缓存key：上次采集时间
    const LAST_RUN_KEY = 'collection_task_last_run';
    // 缓存key前缀：正在运行的采集任务
    const RUNNING_TASK_KEY = 'collection_task_running_';

    /**
     * 根据 CollectSource 配置触发采集
     * @param int $sourceId collect_sources 表中的站点ID
     * @param int $limit 本次采集数量（每页数量）
     * @param array $typeIds 指定分类ID
     * @return array
     */
    public static function triggerBySourceId(int $sourceId, int $limit = 100, array $typeIds = []): array
    {
        $source = CollectSource::find($sourceId);
        if (!$source) {
            return [
                'started' => false,
                'msg' => '采集站点不存在',
            ];
        }

        if (!$source->status) {
            return [
                'started' => false,
                'msg' => '采集站点已禁用',
            ];
        }

        $apiUrl = trim($source->api_url);
        if (empty($apiUrl)) {
            return [
                'started' => false,
                'msg' => '采集站点接口地址为空',
            ];
        }

        $siteInfo = [
            'id' => $source->id,
            'name' => $source->name,
            'description' => $source->description ?? '',
        ];

         return self::trigger($apiUrl, $limit, $typeIds, $siteInfo, $source->page_count ?: null);
    }

    /**
     * 触发采集
     * 只负责从资源站获取视频列表并缓存，初始化进度，不执行实际处理
     * 如果已有运行中的任务，则不再重新获取视频列表
     * @param string $apiUrl 接口地址
     * @param int $limit 每页数量
     * @param array $typeIds 指定分类ID
     * @param array $siteInfo 站点信息
     * @param int $pageCount 资源站总页数（从测试连接获取）
     */
    public static function trigger(string $apiUrl, int $limit = 100, array $typeIds = [], array $siteInfo = [], ?int $pageCount = null): array
    {
        // 记录采集站点（如果不存在）
        $site = self::ensureSourceSite($apiUrl, $siteInfo);
        $collectSourceId = $siteInfo['id'] ?? 0;

        if ($collectSourceId <= 0) {
            return [
                'started' => false,
                'msg' => '缺少采集源ID',
            ];
        }

        // 检查是否已有运行中的任务
        $runningKey = self::RUNNING_TASK_KEY . $collectSourceId;
        $isRunning = Cache::get($runningKey);
        if ($isRunning) {
            $progressKey = self::getProgressCacheKey($collectSourceId);
            $progress = Cache::get($progressKey);
            return [
                'started' => true,
                'msg' => '采集任务已在运行中',
                'total' => $progress['total'] ?? 0,
                'already_running' => true,
            ];
        }

        // 创建采集服务并获取视频列表
        $service = new AppleCmsService($site, $collectSourceId);
        $listData = $service->getVideoList($typeIds, 1, $limit);
        $total = count($listData['list'] ?? []);

        if ($total === 0) {
            return [
                'started' => false,
                'msg' => '未获取到视频列表',
            ];
        }

        // 如果有 page_count，获取剩余页的数据
        if ($pageCount !== null && $pageCount > 1) {
            $allList = $listData['list'];
            for ($page = 2; $page <= $pageCount; $page++) {
                try {
                    $pageData = $service->getVideoList($typeIds, $page, $limit);
                    if (!empty($pageData['list'])) {
                        $allList = array_merge($allList, $pageData['list']);
                    }
                } catch (\Exception $e) {
                    Log::warning('[CollectionTask] 获取第 ' . $page . ' 页失败: ' . $e->getMessage());
                }
            }
            $listData['list'] = $allList;
            $total = count($allList);
        }

        // 缓存视频列表
        $cacheKey = self::getListCacheKey($collectSourceId);
        $listSet = Cache::set($cacheKey, $listData, 3600);

        // 重置处理索引
        $indexKey = self::getIndexCacheKey($collectSourceId);
        $indexSet = Cache::set($indexKey, 0, 3600);

        if (!$listSet || !$indexSet) {
            Log::error('[CollectionTask] 缓存写入失败，source_id=' . $collectSourceId);
            return [
                'started' => false,
                'msg' => '缓存写入失败，请检查 runtime/cache 目录权限',
            ];
        }

        // 标记任务为运行中
        Cache::set($runningKey, true, 3600);

        // 记录上次采集时间
        Cache::set(self::LAST_RUN_KEY, time(), 86400);

        // 初始化进度
        $progressKey = self::getProgressCacheKey($collectSourceId);
        Cache::set($progressKey, [
            'status' => 'running',
            'total' => $total,
            'current' => 0,
            'percent' => 0,
            'msg' => '准备处理，共 ' . $total . ' 个视频',
            'updated_at' => time(),
        ], 3600);
        Log::info('[CollectionTask] 准备处理，共 ' . $total . ' 个视频');

        return [
            'started' => true,
            'msg' => '采集已开始，共 ' . $total . ' 个视频',
            'total' => $total,
        ];
    }

    /**
     * 处理下一个视频
     * 由前端轮询调用，每次只处理一个视频，避免请求超时
     */
    public static function processNext(int $collectSourceId): array
    {
        if ($collectSourceId <= 0) {
            return ['status' => 'failed',    'msg' => '参数错误'];
        }
        Log::info('[CollectionTask] 开始处理下一个视频');

        $cacheKey = self::getListCacheKey($collectSourceId);
        $indexKey = self::getIndexCacheKey($collectSourceId);
        $progressKey = self::getProgressCacheKey($collectSourceId);

        $listData = Cache::get($cacheKey);
        if (empty($listData['list'])) {
            // 缓存列表为空，检查是否仍有运行中标记
            $runningKey2 = self::RUNNING_TASK_KEY . $collectSourceId;
            $isRunning = Cache::get($runningKey2);
            if (!$isRunning) {
                Log::warning('[CollectionTask] 缓存列表为空且无运行中任务，source_id=' . $collectSourceId);
            }
        }

        if (empty($listData['list'])) {
            // 没有缓存列表，可能是已经完成或没有触发过
            $progress = Cache::get($progressKey);
            if ($progress) {
                return ['status' => $progress['status'], 'msg' => $progress['msg']];
            }
            return ['status' => 'idle', 'msg' => '没有待处理的采集任务'];
        }

        $total = count($listData['list']);
        $index = intval(Cache::get($indexKey, 0));

        if ($index >= $total) {
            // 已经处理完
            self::clearCollectCache($collectSourceId);
            Cache::set($progressKey, [
                'status' => 'completed',
                'total' => $total,
                'current' => $total,
                'percent' => 100,
                'msg' => '采集完成',
                'updated_at' => time(),
            ], 300);
            return ['status' => 'completed', 'msg' => '采集完成'];
        }

        $item = $listData['list'][$index];
        $current = $index + 1;
        $percent = $total > 0 ? floor(($current / $total) * 100) : 0;

        // 更新进度为处理中
        Cache::set($progressKey, [
            'status' => 'running',
            'total' => $total,
            'current' => $current,
            'percent' => $percent,
            'msg' => "正在处理第 {$current}/{$total} 个视频",
            'updated_at' => time(),
        ], 3600);

        try {
            $service = self::createServiceBySourceId($collectSourceId);
            if (!$service) {
                throw new \Exception('无法初始化采集服务');
            }

            $vodId = $item['vod_id'] ?? '';
            if (empty($vodId)) {
                Log::warning('[CollectionTask] 缺少 vod_id，跳过第 ' . $current . ' 个');
            } else {
                Log::info('[CollectionTask] 开始处理第 ' . $current . '/' . $total . ' 个视频，vod_id=' . $vodId);
                $detail = $service->getVideoDetail($vodId);
                if (empty($detail)) {
                    // 获取详情失败，尝试使用列表基础数据入库（先转换字段格式）
                    Log::warning('[CollectionTask] 获取详情失败，尝试使用列表数据入库，vod_id=' . $vodId);
                    try {
                        $videoData = self::transformListItemToVideoData($item);
                        $video = $service->saveVideo($videoData);
                        Log::info('[CollectionTask] 使用列表数据保存成功，id=' . ($video->id ?? 0) . ', title=' . ($item['vod_name'] ?? ''));
                    } catch (\Exception $saveError) {
                        if (strpos($saveError->getMessage(), '视频已存在') !== false) {
                            Log::info('[CollectionTask] 视频已存在，跳过，vod_id=' . $vodId);
                        } else {
                            Log::warning('[CollectionTask] 列表数据入库失败: ' . $saveError->getMessage() . '，跳过，vod_id=' . $vodId);
                        }
                    }
                } else {
                    try {
                        $video = $service->saveVideo($detail);
                        Log::info('[CollectionTask] 保存视频成功，id=' . ($video->id ?? 0) . ', title=' . ($detail['vod_name'] ?? ''));
                    } catch (\Exception $saveError) {
                        if (strpos($saveError->getMessage(), '视频已存在') !== false) {
                            Log::info('[CollectionTask] 视频已存在，跳过，vod_id=' . $vodId);
                            // 已存在不算失败，继续下一个
                        } else {
                            throw $saveError;
                        }
                    }
                }
            }

            // 处理成功，移动到下一个
            $setResult = Cache::set($indexKey, $current, 3600);
            if (!$setResult) {
                Log::error('[CollectionTask] 索引缓存写入失败，source_id=' . $collectSourceId . ', current=' . $current);
            }

            // 检查是否已处理完
            if ($current >= $total) {
                self::clearCollectCache($collectSourceId);
                Cache::set($progressKey, [
                    'status' => 'completed',
                    'total' => $total,
                    'current' => $total,
                    'percent' => 100,
                    'msg' => '采集完成',
                    'updated_at' => time(),
                ], 300);
                return ['status' => 'completed', 'msg' => '采集完成'];
            }

            return [
                'status' => 'running',
                'msg' => "已处理第 {$current}/{$total} 个视频",
                'total' => $total,
                'current' => $current,
                'percent' => $percent,
            ];
        } catch (\Throwable $e) {
            Log::error('[CollectionTask] 处理视频失败: ' . $e->getMessage());

            // 单个视频失败，继续下一个（避免卡住）
            Cache::set($indexKey, $current, 3600);

            Cache::set($progressKey, [
                'status' => 'running',
                'total' => $total,
                'current' => $current,
                'percent' => $percent,
                'msg' => '第 ' . $current . ' 个处理失败: ' . $e->getMessage() . '，继续下一个',
                'updated_at' => time(),
            ], 3600);

            return [
                'status' => 'running',
                'msg' => '第 ' . $current . ' 个处理失败，继续下一个',
                'total' => $total,
                'current' => $current,
                'percent' => $percent,
            ];
        }
    }

    /**
     * 获取某个采集源的任务进度
     */
    public static function getProgress(int $collectSourceId): array
    {
        $progressKey = self::getProgressCacheKey($collectSourceId);
        $progress = Cache::get($progressKey);

        if ($progress) {
            return $progress;
        }

        $cacheKey = self::getListCacheKey($collectSourceId);
        if (Cache::get($cacheKey)) {
            return [
                'status' => 'running',
                'total' => 0,
                'current' => 0,
                'percent' => 0,
                'msg' => '等待前端驱动处理',
                'updated_at' => time(),
            ];
        }

        return [
            'status' => 'idle',
            'total' => 0,
            'current' => 0,
            'percent' => 0,
            'msg' => '暂无采集任务',
        ];
    }

    /**
     * 获取任务状态
     */
    public static function getStatus(): array
    {
        return [
            'last_run' => (int) Cache::get(self::LAST_RUN_KEY, 0),
        ];
    }

    /**
     * 强制重置采集任务
     */
    public static function reset(int $collectSourceId = 0): array
    {
        if ($collectSourceId > 0) {
            self::clearCollectCache($collectSourceId);
            Cache::delete(self::getProgressCacheKey($collectSourceId));
        } else {
            // 清除所有采集相关缓存（简单匹配前缀）
            // 文件缓存不支持按前缀删除，这里只清除常见的 key
            Cache::delete(self::LAST_RUN_KEY);
        }

        Log::info('[CollectionTask] 强制重置采集任务: source_id=' . $collectSourceId);

        return ['reset' => true];
    }

    /**
     * 获取或创建采集站点
     */
    protected static function ensureSourceSite(string $apiUrl, array $siteInfo = []): SourceSite
    {
        if (stripos($apiUrl, 'api.php/provide/vod') !== false) {
            $apiUrl = preg_replace('/\?.*$/', '', $apiUrl);
        }
        $apiUrl = rtrim($apiUrl, '/');

        $site = SourceSite::where('api_url', $apiUrl)->find();
        $sourceName = !empty($siteInfo['name']) ? $siteInfo['name'] : '采集源';
        $sourceDesc = $siteInfo['description'] ?? '';

        if (!$site) {
            $site = new SourceSite();
            $site->name = $sourceName;
            $site->code = 'collect_' . (!empty($siteInfo['id']) ? $siteInfo['id'] : uniqid());
            $site->api_url = $apiUrl;
            $site->description = $sourceDesc;
            $site->status = SourceSite::STATUS_ENABLED;
            $site->sort_order = 100;
            $site->save();
        } else {
            $needSave = false;
            if ($site->name !== $sourceName) {
                $site->name = $sourceName;
                $needSave = true;
            }
            if ($site->description !== $sourceDesc) {
                $site->description = $sourceDesc;
                $needSave = true;
            }
            if ($needSave) {
                $site->save();
            }
        }
        return $site;
    }

    /**
     * 根据采集源ID创建 AppleCmsService
     */
    protected static function createServiceBySourceId(int $collectSourceId): ?AppleCmsService
    {
        $source = CollectSource::find($collectSourceId);
        if (!$source) {
            return null;
        }

        $apiUrl = trim($source->api_url);
        if (stripos($apiUrl, 'api.php/provide/vod') !== false) {
            $apiUrl = preg_replace('/\?.*$/', '', $apiUrl);
        }
        $apiUrl = rtrim($apiUrl, '/');

        $site = SourceSite::where('api_url', $apiUrl)->find();
        if (!$site) {
            // 尝试查找未处理过的原始地址
            $site = SourceSite::where('api_url', trim($source->api_url))->find();
            if (!$site) {
                return null;
            }
        }

        return new AppleCmsService($site, $collectSourceId);
    }

    /**
     * 将视频列表数据转换为数据库字段格式
     * 用于详情接口失败时，使用列表基础数据入库
     */
    protected static function transformListItemToVideoData(array $item): array
    {
        return [
            'vod_name'      => $item['vod_name'] ?? '',
            'vod_pic'       => $item['vod_pic'] ?? '',
            'vod_pic_slide' => $item['vod_pic_slide'] ?? '',
            'vod_director'  => $item['vod_director'] ?? '',
            'vod_actor'     => $item['vod_actor'] ?? '',
            'vod_content'   => $item['vod_content'] ?? '',
            'vod_duration'  => $item['vod_duration'] ?? '',
            'vod_year'      => $item['vod_year'] ?? '',
            'vod_area'      => $item['vod_area'] ?? '',
            'vod_lang'      => $item['vod_lang'] ?? '',
            'vod_score'     => $item['vod_score'] ?? '',
            'vod_play_url'  => $item['vod_play_url'] ?? '',
            'type_name'     => $item['type_name'] ?? '',
            'type_id'       => $item['type_id'] ?? 0,
        ];
    }

    /**
     * 清除采集相关缓存
     */
    protected static function clearCollectCache(int $collectSourceId): void
    {
        Cache::delete(self::getListCacheKey($collectSourceId));
        Cache::delete(self::getIndexCacheKey($collectSourceId));
        $runningKey = self::RUNNING_TASK_KEY . $collectSourceId;
        Cache::delete($runningKey);
    }

    protected static function getListCacheKey(int $collectSourceId): string
    {
        return 'collection_video_list_' . $collectSourceId;
    }

    protected static function getIndexCacheKey(int $collectSourceId): string
    {
        return 'collection_process_index_' . $collectSourceId;
    }

    protected static function getProgressCacheKey(int $collectSourceId): string
    {
        return 'collection_progress_' . $collectSourceId;
    }
}
