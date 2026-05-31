<?php
/**
 * 生成测试数据脚本
 */
require __DIR__ . '/../backend/vendor/autoload.php';

// 数据库配置
$dbPath = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "开始生成测试数据...\n";
    
    // 生成视频数据
    $videos = [
        ['title' => '流浪地球2', 'type' => 1, 'cover' => 'https://picsum.photos/seed/movie1/300/400', 'rating' => 8.5, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '郭帆', 'description' => '太阳即将毁灭，人类在地球表面建造出巨大的推进器，寻找新的家园。'],
        ['title' => '满江红', 'type' => 1, 'cover' => 'https://picsum.photos/seed/movie2/300/400', 'rating' => 7.8, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '张艺谋', 'description' => '南宋绍兴年间，秦桧率兵与金国会谈。会谈前夜，金国使者死在宰相驻地。'],
        ['title' => '狂飙', 'type' => 2, 'cover' => 'https://picsum.photos/seed/tv1/300/400', 'rating' => 9.1, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '徐纪周', 'description' => '京海市一线刑警安欣，在与黑恶势力的斗争中，不断遭到保护伞的打击。'],
        ['title' => '漫长的季节', 'type' => 2, 'cover' => 'https://picsum.photos/seed/tv2/300/400', 'rating' => 9.2, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '辛爽', 'description' => '小城桦林，一家人的命运在时代洪流中翻天覆地。'],
        ['title' => '进击的巨人 最终季', 'type' => 3, 'cover' => 'https://picsum.photos/seed/anime1/300/400', 'rating' => 9.5, 'region' => '日本', 'release_year' => '2023', 'director' => '林祐一郎', 'description' => '讲述人类与巨人的生死之战。'],
        ['title' => '鬼灭之刃 刀匠村篇', 'type' => 3, 'cover' => 'https://picsum.photos/seed/anime2/300/400', 'rating' => 8.8, 'region' => '日本', 'release_year' => '2023', 'director' => '外崎春雄', 'description' => '炭治郎为了救回祢豆子，踏上新的旅程。'],
        ['title' => '搞笑短视频合集', 'type' => 4, 'cover' => 'https://picsum.photos/seed/short1/300/400', 'rating' => 7.5, 'region' => '中国大陆', 'release_year' => '2023', 'director' => ' Various', 'description' => '各种搞笑短视频精彩合集'],
        ['title' => '宇宙探索纪录片', 'type' => 5, 'cover' => 'https://picsum.photos/seed/doc1/300/400', 'rating' => 9.0, 'region' => '美国', 'release_year' => '2023', 'director' => 'various', 'description' => '探索宇宙奥秘的纪录片'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO videos (title, type, cover, rating, region, release_year, director, description, play_count, is_vip, is_show, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))");
    
    foreach ($videos as $video) {
        $playCount = rand(1000, 100000);
        $isVip = rand(0, 10) > 7 ? 1 : 0; // 30%概率是VIP
        
        $stmt->execute([
            $video['title'],
            $video['type'],
            $video['cover'],
            $video['rating'],
            $video['region'],
            $video['release_year'],
            $video['director'],
            $video['description'],
            $playCount,
            $isVip,
            1
        ]);
        
        $videoId = $pdo->lastInsertId();
        echo "插入视频: {$video['title']} (ID: {$videoId})\n";
        
        // 如果是电视剧，插入剧集
        if ($video['type'] == 2 || $video['type'] == 3) {
            $episodeCount = rand(10, 24);
            for ($i = 1; $i <= $episodeCount; $i++) {
                $pdo->exec("INSERT INTO video_sources (video_id, source_site_id, name, play_url, sort_order, status, created_at, updated_at) VALUES ({$videoId}, 1, '第{$i}集', 'https://example.com/play/{$videoId}/{$i}', {$i}, 1, datetime('now'), datetime('now'))");
            }
            echo "  -> 插入 {$episodeCount} 个剧集\n";
        } else {
            // 电影/短视频插入一个播放地址
            $pdo->exec("INSERT INTO video_sources (video_id, source_site_id, name, play_url, sort_order, status, created_at, updated_at) VALUES ({$videoId}, 1, '正片', 'https://example.com/play/{$videoId}', 0, 1, datetime('now'), datetime('now'))");
        }
    }
    
    // 生成分类数据
    $categories = [
        ['name' => '动作片', 'slug' => 'action', 'type' => 1],
        ['name' => '喜剧片', 'slug' => 'comedy', 'type' => 1],
        ['name' => '科幻片', 'slug' => 'scifi', 'type' => 1],
        ['name' => '国产剧', 'slug' => 'domestic-drama', 'type' => 2],
        ['name' => '日剧', 'slug' => 'japanese-drama', 'type' => 2],
        ['name' => '热血动漫', 'slug' => 'action-anime', 'type' => 3],
        ['name' => '搞笑短视频', 'slug' => 'funny-short', 'type' => 4],
        ['name' => '自然纪录片', 'slug' => 'nature-doc', 'type' => 5],
    ];
    
    $stmtCat = $pdo->prepare("INSERT INTO categories (name, slug, type, sort_order, created_at) VALUES (?, ?, ?, ?, datetime('now'))");
    
    foreach ($categories as $index => $cat) {
        $stmtCat->execute([$cat['name'], $cat['slug'], $cat['type'], $index + 1]);
        echo "插入分类: {$cat['name']}\n";
    }
    
    echo "\n测试数据生成完成！\n";
    echo "共插入 " . count($videos) . " 个视频\n";
    echo "共插入 " . count($categories) . " 个分类\n";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
