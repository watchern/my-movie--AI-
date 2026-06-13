<?php
// 创建轮播图表的脚本
$dbPath = __DIR__ . '/movie.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "
CREATE TABLE IF NOT EXISTS banners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type INTEGER DEFAULT 1,
    video_id INTEGER DEFAULT 0,
    title VARCHAR(255) DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    link_url VARCHAR(500) DEFAULT NULL,
    sort_order INTEGER DEFAULT 100,
    status INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_banners_type ON banners(type);
CREATE INDEX IF NOT EXISTS idx_banners_status ON banners(status);
CREATE INDEX IF NOT EXISTS idx_banners_sort ON banners(sort_order);
";
    
    $pdo->exec($sql);
    
    echo "轮播图表创建成功！\n";
    echo "表结构：\n";
    $result = $pdo->query("PRAGMA table_info(banners)");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['name']} ({$row['type']})\n";
    }
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage() . "\n";
}