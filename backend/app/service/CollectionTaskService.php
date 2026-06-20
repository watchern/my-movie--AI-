<?php

namespace app\service;

use app\model\CollectSource;
use app\model\SourceSite;
use think\facade\Cache;
use think\facade\Log;

/**
 * 异步采集任务服务
 * 使用缓存队列 + 后台 worker 模式，避免前端请求超时
 */
class CollectionTaskService
{
    // 缓存key：标记是否有采集任务正在执行
    const RUNNING_KEY = 'collection_task_running';
    // 缓存key：任务队列
    const TASK_QUEUE_KEY = 'collection_task_queue';
    // 缓存key：worker进程锁
    const WORKER_LOCK_KEY = 'collection_worker_lock';
    // 缓存key：上次采集时间
    const LAST_RUN_KEY = 'collection_task_last_run';

    /**
     * 检查是否有采集任务正在执行
     */
    public static function isRunning(): bool
    {
        return (bool) Cache::get(self::RUNNING_KEY, false);
    }

    /**
     * 标记采集任务执行状态
     */
    public static function setRunning(bool $running): bool
    {
        if ($running) {
            Cache::set(self::RUNNING_KEY, true, 3600);
        } else {
            Cache::delete(self::RUNNING_KEY);
        }
        return true;
    }

    /**
     * 根据 CollectSource 配置触发异步采集
     * @param int $sourceId collect_sources 表中的站点ID
     * @param int $limit 本次采集数量
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

        return self::trigger($apiUrl, $limit, $typeIds, $siteInfo);
    }

    /**
     * 触发异步采集
     * @param string $apiUrl 采集接口地址，例如 http://caiji.dyttzyapi.com/api.php/provide/vod
     * @param int $limit 本次采集数量
     * @param array $typeIds 指定分类ID
     * @param array $siteInfo 站点信息 ['id' => ..., 'name' => ..., 'description' => ...]
     * @return array
     */
    public static function trigger(string $apiUrl, int $limit = 100, array $typeIds = [], array $siteInfo = []): array
    {
        // 记录采集站点（如果不存在）
        $site = self::ensureSourceSite($apiUrl, $siteInfo);

        $collectSourceId = $siteInfo['id'] ?? 0;

        // 构建任务
        $task = [
            'site_id' => $site->id,
            'collect_source_id' => $collectSourceId,
            'api_url' => $site->api_url,
            'limit' => $limit,
            'type_ids' => $typeIds,
            'created_at' => time(),
        ];

        // 加入队列
        $queue = Cache::get(self::TASK_QUEUE_KEY, []);
        $queue[] = $task;
        Cache::set(self::TASK_QUEUE_KEY, $queue, 86400);

        // 记录上次采集时间
        Cache::set(self::LAST_RUN_KEY, time(), 86400);

        // 启动后台 worker（如果未运行）
        self::startWorkerIfNeeded();

        return [
            'started' => true,
            'msg' => '采集任务已加入队列',
        ];
    }

    /**
     * 获取或创建采集站点
     * 兼容用户配置完整接口地址的情况
     * @param array $siteInfo 采集源信息 ['id' => ..., 'name' => ..., 'description' => ...]
     */
    protected static function ensureSourceSite(string $apiUrl, array $siteInfo = []): SourceSite
    {
        // 如果包含完整接口路径，去掉查询参数
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
            // 如果名称或描述发生变化，同步更新
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
     * 启动后台 worker（幂等）
     */
    protected static function startWorkerIfNeeded(): void
    {
        // 检查 worker 锁，避免重复启动
        if (Cache::get(self::WORKER_LOCK_KEY)) {
            Log::info('[CollectionTask] worker锁存在，跳过启动');
            return;
        }

        // 设置 worker 锁（60秒后自动释放，防止 worker 异常退出导致死锁）
        Cache::set(self::WORKER_LOCK_KEY, time(), 60);

        $workerPath = dirname(__DIR__) . '/collect_worker.php';
        $cmd = 'php ' . escapeshellarg($workerPath);

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        try {
            if ($isWindows) {
                // Windows 使用 start /B 后台启动
                $shellCmd = 'start /B "" ' . $cmd;
                Log::info('[CollectionTask] 启动worker命令: ' . $shellCmd);
                pclose(popen($shellCmd, 'r'));
            } else {
                // Linux/Mac 使用 nohup 后台启动
                $shellCmd = 'nohup ' . $cmd . ' > /dev/null 2>&1 &';
                Log::info('[CollectionTask] 启动worker命令: ' . $shellCmd);
                exec($shellCmd);
            }
            Log::info('[CollectionTask] worker已启动');
        } catch (\Throwable $e) {
            Log::error('[CollectionTask] 启动worker失败: ' . $e->getMessage());
            Cache::delete(self::WORKER_LOCK_KEY);
        }
    }

    /**
     * worker 主循环，处理任务队列
     * 供 collect_worker.php 调用
     */
    public static function runWorker(): void
    {
        // 设置 worker 锁，防止多个 worker 同时运行
        Cache::set(self::WORKER_LOCK_KEY, time(), 300);

        $startTime = time();
        $maxRunTime = 240; // 最多运行4分钟

        try {
            while (time() - $startTime < $maxRunTime) {
                $queue = Cache::get(self::TASK_QUEUE_KEY, []);
                if (empty($queue)) {
                    break;
                }

                // 取出第一个任务
                $task = array_shift($queue);
                Cache::set(self::TASK_QUEUE_KEY, $queue, 86400);

                // 执行任务
                $site = SourceSite::find($task['site_id'] ?? 0);
                if ($site) {
                    self::runCollect(
                        $site,
                        intval($task['limit'] ?? 100),
                        $task['type_ids'] ?? [],
                        intval($task['collect_source_id'] ?? 0)
                    );
                }

                // 刷新 worker 锁
                Cache::set(self::WORKER_LOCK_KEY, time(), 300);
            }
        } catch (\Throwable $e) {
            Log::error('[CollectionTask] worker异常: ' . $e->getMessage());
        } finally {
            Cache::delete(self::WORKER_LOCK_KEY);
            Cache::delete(self::RUNNING_KEY);
        }
    }

    /**
     * 执行采集
     */
    public static function runCollect(SourceSite $site, int $limit, array $typeIds, int $collectSourceId = 0): void
    {
        try {
            Log::info("[CollectionTask] 开始采集，站点: {$site->api_url}, limit: {$limit}");
            self::setRunning(true);

            // 写入初始 running 进度，让前端尽快看到状态变化
            if ($collectSourceId > 0) {
                Cache::set('collection_progress_' . $collectSourceId, [
                    'status' => 'running',
                    'total' => 0,
                    'current' => 0,
                    'percent' => 0,
                    'msg' => '开始采集',
                    'updated_at' => time(),
                ], 3600);
            }

            $service = new AppleCmsService($site, $collectSourceId);
            $result = $service->collectToLocal($typeIds, $limit);
            Log::info('[CollectionTask] 采集完成: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            Log::error('[CollectionTask] 采集异常: ' . $e->getMessage());
            if ($collectSourceId > 0) {
                Cache::set('collection_progress_' . $collectSourceId, [
                    'status' => 'failed',
                    'total' => 0,
                    'current' => 0,
                    'percent' => 0,
                    'msg' => '采集异常: ' . $e->getMessage(),
                    'updated_at' => time(),
                ], 300);
            }
        } finally {
            self::setRunning(false);
        }
    }

    /**
     * 获取某个采集源的任务进度
     */
    public static function getProgress(int $collectSourceId): array
    {
        $key = 'collection_progress_' . $collectSourceId;
        $progress = Cache::get($key);

        if ($progress) {
            return $progress;
        }

        // 没有进度缓存，检查是否还在排队中
        $queue = Cache::get(self::TASK_QUEUE_KEY, []);
        foreach ($queue as $task) {
            if (intval($task['collect_source_id'] ?? 0) === $collectSourceId) {
                return [
                    'status' => 'pending',
                    'total' => 0,
                    'current' => 0,
                    'percent' => 0,
                    'msg' => '任务排队中，等待执行',
                ];
            }
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
            'running' => self::isRunning(),
            'last_run' => (int) Cache::get(self::LAST_RUN_KEY, 0),
            'can_start' => true,
            'queue_length' => count(Cache::get(self::TASK_QUEUE_KEY, [])),
        ];
    }
}
