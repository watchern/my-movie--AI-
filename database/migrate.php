<?php
/**
 * 数据库迁移脚本
 * 用法：php database/migrate.php
 */

require __DIR__ . '/../backend/vendor/autoload.php';

use think\facade\Db;
use think\App;

// 初始化 ThinkPHP 应用
$app = new App(__DIR__ . '/../backend');
$app->initialize();

// 获取当前数据库类型
$type = config('database.default', 'sqlite');

echo "当前数据库类型: {$type}\n";

// 为 collect_sources 表添加 description 字段（如果不存在）
if ($type === 'sqlite') {
    // SQLite 不支持 IF NOT EXISTS 添加列，需要查询 pragma
    $columns = Db::query("PRAGMA table_info(collect_sources)");
    $hasDescription = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'description') {
            $hasDescription = true;
            break;
        }
    }

    if (!$hasDescription) {
        Db::execute("ALTER TABLE collect_sources ADD COLUMN description VARCHAR(500) DEFAULT NULL");
        echo "已添加 collect_sources.description 字段\n";
    } else {
        echo "collect_sources.description 字段已存在\n";
    }
} else {
    // MySQL
    try {
        Db::execute("ALTER TABLE collect_sources ADD COLUMN IF NOT EXISTS description VARCHAR(500) DEFAULT NULL COMMENT '资源描述'");
        echo "已添加 collect_sources.description 字段\n";
    } catch (\Throwable $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "collect_sources.description 字段已存在\n";
        } else {
            echo "添加字段失败: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

echo "迁移完成\n";
