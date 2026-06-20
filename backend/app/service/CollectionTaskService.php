<?php

namespace app\service;

use app\model\SourceSite;
use app\model\Video;
use app\model\VideoSource;
use think\facade\Cache;
use think\facade\Log;

/**
 * 异步采集任务服务
 */
class CollectionTaskService
{
    // 缓存key：标记是否有采集任务进行中
    const RUNNING_KEY = 'collection_task_running';
    // 缓存key：上次采集时间
    const LAST_RUN_KEY = 'collection_task_last_run';
    // 最小间隔时间（秒），防止频繁触发
    const MIN_INTERVAL = 300;

    /**
     * 检查是否有采集任务进行中
     */
    public static function isRunning(): bool
    {
        return (bool) Cache::get(self::RUNNING_KEY, false);
    }

    /**
     * 标记采集任务状态
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
     * 检查是否允许启动新任务
     */
    public static function canStart(): bool
    {
        if (self::isRunning()) {
            return false;
        }
        $lastRun = (int) Cache::get(self::LAST_RUN_KEY, 0);
        if (time() - $lastRun < self::MIN_INTERVAL) {
            return false;
        }
        return true;
    }

    /**
     * 触发异步采集
     * @param string $apiUrl 采集接口地址，例如 http://caiji.dyttzyapi.com/api.php/provide/vod
     * @param int $limit 本次采集数量
     * @param array $typeIds 指定分类ID
     * @return array
     */
    public static function trigger(string $apiUrl, int $limit = 100, array $typeIds = []): array
    {
        if (!self::canStart()) {
            return [
                'started' => false,
                'msg' => '已有采集任务进行中或间隔时间太短',
            ];
        }

        self::setRunning(true);
        Cache::set(self::LAST_RUN_KEY, time(), 86400);

        // 记录采集站点（如果不存在）
        $site = self::ensureSourceSite($apiUrl);

        // 在后台执行采集
        if (function_exists('fastcgi_finish_request')) {
            // 注册关闭回调执行采集
            register_shutdown_function(function () use ($site, $limit, $typeIds) {
                self::runCollect($site, $limit, $typeIds);
            });
            // 立即结束请求，让客户端继续
            fastcgi_finish_request();
        } else {
            // CLI 或无 fastcgi 环境，同步执行
            self::runCollect($site, $limit, $typeIds);
        }

        return [
            'started' => true,
            'msg' => '采集任务已启动',
        ];
    }

    /**
     * 获取或创建采集站点
     */
    protected static function ensureSourceSite(string $apiUrl): SourceSite
    {
        $apiUrl = rtrim($apiUrl, '/');
        $site = SourceSite::where('api_url', $apiUrl)->find();
        if (!$site) {
            $site = new SourceSite();
            $site->name = 'dyttzy采集源';
            $site->code = 'dyttzy';
            $site->api_url = $apiUrl;
            $site->status = SourceSite::STATUS_ENABLED;
            $site->sort_order = 100;
            $site->save();
        }
        return $site;
    }

    /**
     * 执行采集
     */
    public static function runCollect(SourceSite $site, int $limit, array $typeIds): void
    {
        try {
            Log::info("[CollectionTask] 开始采集，站点: {$site->api_url}, limit: {$limit}");
            $service = new AppleCmsService($site);
            $result = $service->collectToLocal($typeIds, $limit);
            Log::info('[CollectionTask] 采集完成: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            Log::error('[CollectionTask] 采集异常: ' . $e->getMessage());
        } finally {
            self::setRunning(false);
        }
    }

    /**
     * 获取任务状态
     */
    public static function getStatus(): array
    {
        return [
            'running' => self::isRunning(),
            'last_run' => (int) Cache::get(self::LAST_RUN_KEY, 0),
            'can_start' => self::canStart(),
        ];
    }
}
