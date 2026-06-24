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
     * 如果已有运行中的任务则继续处理下一个视频，否则启动新任务
     * @param int $sourceId collect_sources 表中的站点ID
     * @param int $limit 每页数量
     * @param array $typeIds 指定分类ID
     * @return array
     */
    public static function triggerBySourceId(int $sourceId, int $limit = 20, array $typeIds = []): array
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

        $runningKey = self::RUNNING_TASK_KEY . $sourceId;
        $isRunning = Cache::get($runningKey);

        if ($isRunning) {
            return self::processNext($sourceId);
        }

         $pageCount = $source->page_count ?: null;

          return self::trigger($apiUrl, $limit, $typeIds, $siteInfo, $pageCount);
     }

    /**
     * 触发采集
     * 从上次采集的页码继续采集，如果无记录则从第一页开始
     * @param string $apiUrl 接口地址
     * @param int $limit 每页数量
     * @param array $typeIds 指定分类ID
     * @param array $siteInfo 站点信息
     * @param int|null $pageCount 资源站总页数
     * @param int $resumeFromPage 从哪一页开始采集（断点续采）
     */
    public static function trigger(string $apiUrl, int $limit = 20, array $typeIds = [], array $siteInfo = [], ?int $pageCount = null): array
    {
          set_time_limit(120);

          $site = self::ensureSourceSite($apiUrl, $siteInfo);
          $collectSourceId = $siteInfo['id'] ?? 0;

          if ($collectSourceId <= 0) {
              return [
                  'started' => false,
                  'msg' => '缺少采集源ID',
              ];
          }

          $source = CollectSource::find($collectSourceId);
          $runningKey = self::RUNNING_TASK_KEY . $collectSourceId;
          $service = new AppleCmsService($site, $collectSourceId);

          $lastVodId = $source->last_collected_vod_id ?? '';
          $lastCollectedPage = intval($source->last_collected_page ?? 0);
          $lastCollectedAt = $source->last_collected_at ?? '';
          $lastVodName = $source->last_vod_name ?? '';
          $lastVodPic = $source->last_vod_pic ?? '';
          $lastVodYear = $source->last_vod_year ?? '';
          $lastVodScore = $source->last_vod_score ?? 0;
          $lastVodDuration = $source->last_vod_duration ?? 0;

          $shouldResume = !empty($lastVodId) && $lastCollectedPage > 0
              && (strtotime($lastCollectedAt) > time() - 7200);

          if ($shouldResume) {
              $currentPage = $lastCollectedPage;
          } else {
              $currentPage = $pageCount ?: 1;
          }

          if (!$shouldResume && empty($pageCount)) {
              $testData = $service->getVideoList([], 1, $limit);
              $apiPageCount = intval($testData['pagecount'] ?? 0);
              if ($apiPageCount > 0) {
                  $pageCount = $apiPageCount;
                  $source->page_count = $pageCount;
                  $source->save();
                  $currentPage = $pageCount;
              }
          }

          $pageData = $service->getVideoList($typeIds, $currentPage, $limit);
          $pageList = $pageData['list'] ?? [];

          $apiPageCount = intval($pageData['pagecount'] ?? 0);
          if ($apiPageCount > 0 && $apiPageCount !== intval($pageCount)) {
              $pageCount = $apiPageCount;
              $source->page_count = $pageCount;
              $source->save();
          }

          if (empty($pageList)) {
              if ($shouldResume && $currentPage > 0) {
                  $source->last_collected_page = $currentPage - 1;
                  $source->save();
                  return self::trigger($apiUrl, $limit, $typeIds, $siteInfo, $pageCount);
              }
              return [
                  'started' => false,
                  'msg' => "第 {$currentPage} 页没有数据",
              ];
          }

          $foundResumePoint = !$shouldResume;

          $pageList = array_reverse($pageList);

          if (!$foundResumePoint && !empty($lastVodId)) {
              $foundInPage = false;
              $filtered = [];
              foreach ($pageList as $item) {
                  if (!$foundInPage && ($item['vod_id'] ?? '') === $lastVodId) {
                      $foundInPage = true;
                      $foundResumePoint = true;
                      continue;
                  }
                  $filtered[] = $item;
              }
              $pageList = $filtered;
              $skipCount = count($pageData['list'] ?? []) - count($filtered);
              Log::info("[CollectionTask] 断点续采，第{$currentPage}页跳过已采集视频 {$skipCount} 个，从 vod_id={$lastVodId} 之后继续");

              if (!$foundInPage) {
                  $maxPage = $pageCount ?: PHP_INT_MAX;
                  if ($lastCollectedPage + 1 > $maxPage) {
                      $source->last_collected_page = 0;
                      $source->last_collected_vod_id = '';
                      $source->save();
                      Log::warning("[CollectionTask] 断点续采：已到达最大页码，未找到断点视频(vod_id={$lastVodId}, title={$lastVodName})，清空断点记录，从最大页重新采集");
                      return self::trigger($apiUrl, $limit, $typeIds, $siteInfo, $pageCount);
                  }

                  $source->last_collected_page = $lastCollectedPage + 1;
                  $source->save();
                  $vodInfo = "vod_id={$lastVodId}";
                  if ($lastVodName) $vodInfo .= ", title={$lastVodName}";
                  if ($lastVodYear) $vodInfo .= ", year={$lastVodYear}";
                  if ($lastVodScore) $vodInfo .= ", score={$lastVodScore}";
                  Log::warning("[CollectionTask] 断点续采：第{$currentPage}页未找到断点视频({$vodInfo})，前进到第" . $source->last_collected_page . "页，下次继续查找");
                  return [
                      'started' => false,
                      'msg' => "未找到断点视频「{$lastVodName}」(vod_id:{$lastVodId}" . ($lastVodYear ? ", {$lastVodYear}年" : "") . ($lastVodScore ? ", 评分{$lastVodScore}" : "") . ")，前进到第 {$source->last_collected_page} 页，请重试",
                  ];
              }
          }

          $nextPage = $currentPage - 1;

          $cacheKey = self::getListCacheKey($collectSourceId);
          Cache::set($cacheKey, ['list' => $pageList, 'page' => $currentPage, 'next_page' => $nextPage, 'limit' => $limit, 'page_count' => $pageCount, 'type_ids' => $typeIds], 3600);

          $indexKey = self::getIndexCacheKey($collectSourceId);
          Cache::set($indexKey, 0, 3600);

          Cache::set($runningKey, true, 3600);

          Cache::set(self::LAST_RUN_KEY, time(), 86400);

          $source->last_collected_page = $currentPage;
          $source->last_collected_vod_id = '';
          $source->last_collected_at = date('Y-m-d H:i:s');
          $source->save();

          $progressKey = self::getProgressCacheKey($collectSourceId);
          Cache::set($progressKey, [
              'status' => 'running',
              'total' => count($pageList),
              'current' => 0,
              'percent' => 0,
              'msg' => "准备处理第 {$currentPage} 页，共 " . count($pageList) . " 个视频",
              'updated_at' => time(),
          ], 3600);

          $resumeMsg = $shouldResume ? "（从第 {$currentPage} 页续采）" : "（从第 {$currentPage} 页倒序采集）";
          Log::info("[CollectionTask] 采集任务已启动，第 {$currentPage} 页，共 " . count($pageList) . " 个视频 {$resumeMsg}");

          return [
              'started' => true,
              'msg' => "采集已开始，第 {$currentPage} 页，共 " . count($pageList) . " 个视频 {$resumeMsg}",
              'total' => count($pageList),
              'page_count' => $pageCount,
              'current_page' => $currentPage,
          ];
     }

    /**
     * 处理下一个视频
     * 由前端轮询调用，每次只处理一个视频，避免请求超时
     */
    public static function processNext(int $collectSourceId): array
    {
        set_time_limit(60);

        if ($collectSourceId <= 0) {
            return ['status' => 'failed', 'msg' => '参数错误'];
        }
        Log::info('[CollectionTask] 开始处理下一个视频');

        $cacheKey = self::getListCacheKey($collectSourceId);
        $indexKey = self::getIndexCacheKey($collectSourceId);
        $progressKey = self::getProgressCacheKey($collectSourceId);

        $listData = Cache::get($cacheKey);
        if (empty($listData['list'])) {
            $runningKey2 = self::RUNNING_TASK_KEY . $collectSourceId;
            $isRunning = Cache::get($runningKey2);
            if (!$isRunning) {
                Log::warning('[CollectionTask] 缓存列表为空且无运行中任务，source_id=' . $collectSourceId);
            }
        }

        if (empty($listData['list'])) {
            $progress = Cache::get($progressKey);
            if ($progress) {
                return ['status' => $progress['status'], 'msg' => $progress['msg']];
            }
            return ['status' => 'idle', 'msg' => '没有待处理的采集任务'];
        }

        $total = count($listData['list']);
        $index = intval(Cache::get($indexKey, 0));

        if ($index >= $total) {
            $currentPage = intval($listData['page'] ?? 0);
            $nextPage = intval($listData['next_page'] ?? 0);
            $pageCount = intval($listData['page_count'] ?? 0);
            $typeIds = $listData['type_ids'] ?? [];
            $limit = intval($listData['limit'] ?? 100);

            if ($nextPage >= 1) {
                $source = CollectSource::find($collectSourceId);
                $service = self::createServiceBySourceId($collectSourceId);

                $nextPageData = $service->getVideoList($typeIds, $nextPage, $limit);
                $nextPageList = $nextPageData['list'] ?? [];

                $apiPageCount = intval($nextPageData['pagecount'] ?? 0);
                if ($apiPageCount > 0 && $apiPageCount !== intval($pageCount)) {
                    $pageCount = $apiPageCount;
                    $source->page_count = $pageCount;
                    $source->save();
                }

                if (!empty($nextPageList)) {
                    $newNextPage = $nextPage - 1;
                    Cache::set($cacheKey, ['list' => $nextPageList, 'page' => $nextPage, 'next_page' => $newNextPage, 'limit' => $limit, 'page_count' => $pageCount, 'type_ids' => $typeIds], 3600);
                    Cache::set($indexKey, 0, 3600);

                    if ($source) {
                        $source->last_collected_page = $nextPage;
                        $source->last_collected_vod_id = '';
                        $source->last_collected_at = date('Y-m-d H:i:s');
                        $source->save();
                    }

                    Cache::set($progressKey, [
                        'status' => 'running',
                        'total' => count($nextPageList),
                        'current' => 0,
                        'percent' => 0,
                        'msg' => "准备处理第 {$nextPage} 页，共 " . count($nextPageList) . " 个视频",
                        'updated_at' => time(),
                    ], 3600);

                    return [
                        'status' => 'running',
                        'msg' => "第 {$currentPage} 页已完成，加载第 {$nextPage} 页，共 " . count($nextPageList) . " 个视频",
                        'total' => count($nextPageList),
                        'current_page' => $nextPage,
                        'page_count' => $pageCount,
                    ];
                }
            }

            self::clearCollectCache($collectSourceId);

            $sourceModel = CollectSource::find($collectSourceId);
            if ($sourceModel) {
                $sourceModel->last_collected_page = 0;
                $sourceModel->last_collected_vod_id = '';
                $sourceModel->last_collected_vod_name = '';
                $sourceModel->last_collected_vod_pic = '';
                $sourceModel->last_collected_vod_year = '';
                $sourceModel->last_collected_vod_score = 0;
                $sourceModel->last_collected_vod_duration = 0;
                $sourceModel->last_collected_at = date('Y-m-d H:i:s');
                $sourceModel->save();
            }

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

        Cache::set($progressKey, [
            'status' => 'running',
            'total' => $total,
            'current' => $current,
            'percent' => $percent,
            'msg' => "正在处理第 {$current}/{$total} 个视频",
            'vod_name' => $item['vod_name'] ?? '',
            'vod_id' => $item['vod_id'] ?? '',
            'updated_at' => time(),
        ], 3600);

        try {
            $service = self::createServiceBySourceId($collectSourceId);
            if (!$service) {
                throw new \Exception('无法初始化采集服务');
            }

            $vodId = $item['vod_id'] ?? '';
            $vodName = $item['vod_name'] ?? '';
            if (empty($vodId)) {
                Log::warning('[CollectionTask] 缺少 vod_id，跳过第 ' . $current . ' 个');
            } else {
                Log::info('[CollectionTask] 开始处理第 ' . $current . '/' . $total . ' 个视频，vod_id=' . $vodId);

                $hasPlayUrl = !empty($item['vod_play_url']);
                $hasDescription = !empty($item['vod_content']);
                $hasCover = !empty($item['vod_pic']);
                $saveData = $item;

                if (!$hasPlayUrl || !$hasDescription || !$hasCover) {
                    $detail = $service->getVideoDetail($vodId);
                    if (!empty($detail)) {
                        $saveData = $detail;
                    }
                }

                try {
                    $video = $service->saveVideo($saveData);
                    Log::info('[CollectionTask] 保存视频成功，id=' . ($video->id ?? 0) . ', title=' . $vodName);
                } catch (\Exception $saveError) {
                    if (strpos($saveError->getMessage(), '视频已存在') !== false) {
                        Log::info('[CollectionTask] 视频已存在，跳过，vod_id=' . $vodId);
                    } else {
                        throw $saveError;
                    }
                }
            }

            Cache::set($indexKey, $current, 3600);

            $source = CollectSource::find($collectSourceId);
            if ($source) {
                $source->last_collected_vod_id = $vodId;
                $source->last_collected_vod_name = $item['vod_name'] ?? '';
                $source->last_collected_vod_pic = $item['vod_pic'] ?? '';
                $source->last_collected_vod_year = $item['vod_year'] ?? '';
                $source->last_collected_vod_score = floatval($item['vod_score'] ?? 0);
                $source->last_collected_vod_duration = intval($item['vod_duration'] ?? 0);
                $source->last_collected_at = date('Y-m-d H:i:s');
                $source->save();
            }

            if ($service) {
                $service->flushBatchEpisodes();
            }

            return [
                'status' => 'running',
                'msg' => "已处理第 {$current}/{$total} 个视频",
                'total' => $total,
                'current' => $current,
                'percent' => $percent,
                'vod_name' => $item['vod_name'] ?? '',
                'vod_id' => $vodId,
                'current_page' => $listData['page'] ?? 0,
            ];
        } catch (\Throwable $e) {
            Log::error('[CollectionTask] 处理视频失败: ' . $e->getMessage());

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
                'info' => $e->getMessage(),
                'total' => $total,
                'current' => $current,
                'percent' => $percent,
                'vod_name' => $item['vod_name'] ?? '',
                'vod_id' => $vodId,
                'current_page' => $listData['page'] ?? 0,
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
