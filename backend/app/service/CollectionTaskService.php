<?php

namespace app\service;

use app\model\CollectSource;
use app\model\SourceSite;
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
     * 有调用即触发：只要没有进行中的任务就允许启动
     */
    public static function canStart(): bool
    {
        return !self::isRunning();
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

        return self::trigger($apiUrl, $limit, $typeIds);
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
                'msg' => '已有采集任务进行中',
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
     * 兼容用户配置完整接口地址的情况
     */
    protected static function ensureSourceSite(string $apiUrl): SourceSite
    {
        // 如果包含完整接口路径，去掉查询参数
        if (stripos($apiUrl, 'api.php/provide/vod') !== false) {
            $apiUrl = preg_replace('/\?.*$/', '', $apiUrl);
        }
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
