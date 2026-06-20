<?php
/**
 * 采集任务后台 worker
 * 由 CollectionTaskService 启动，处理采集任务队列
 */

// 设置最大执行时间（部分环境可能受限于 CLI 配置）
set_time_limit(300);
ignore_user_abort(true);

// 注册自动加载
require __DIR__ . '/vendor/autoload.php';

use app\service\CollectionTaskService;
use think\App;
use think\facade\Log;

// 初始化 ThinkPHP 应用
$app = new App(__DIR__);
$app->initialize();

Log::info('[CollectionTask] worker进程已启动，开始处理任务队列');

// 执行 worker 主循环
CollectionTaskService::runWorker();

Log::info('[CollectionTask] worker进程已结束');
