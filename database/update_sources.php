<?php
/**
 * 更新视频源为真实可访问地址
 */
require __DIR__ . '/../backend/vendor/autoload.php';

$dbPath = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "开始更新视频源...\n";
    
    // 获取所有视频
    $videos = $pdo->query("SELECT id FROM videos")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($videos as $video) {
        $videoId = $video['id'];
        
        // 删除旧的视频源
        $pdo->exec("DELETE FROM video_sources WHERE video_id = {$videoId}");
        
        // 获取视频类型
        $videoInfo = $pdo->query("SELECT type FROM videos WHERE id = {$videoId}")->fetch(PDO::FETCH_ASSOC);
        $type = $videoInfo['type'];
        
        $playUrls = [
            'https://www.w3schools.com/html/mov_bbb.mp4',
            'https://www.w3schools.com/html/movie.mp4',
        ];
        
        if ($type == 2 || $type == 3) {
            // 电视剧/动漫插入多个剧集
            $episodeCount = rand(10, 24);
            for ($i = 1; $i <= $episodeCount; $i++) {
                $playUrl = $playUrls[$i % 2];
                $pdo->exec("INSERT INTO video_sources (video_id, source_site_id, name, play_url, sort_order, status, created_at, updated_at) VALUES ({$videoId}, 1, '第{$i}集', '{$playUrl}', {$i}, 1, datetime('now'), datetime('now'))");
            }
            echo "视频 {$videoId}: 插入 {$episodeCount} 个剧集\n";
        } else {
            // 电影/短视频插入一个播放地址
            $playUrl = $playUrls[rand(0, 1)];
            $pdo->exec("INSERT INTO video_sources (video_id, source_site_id, name, play_url, sort_order, status, created_at, updated_at) VALUES ({$videoId}, 1, '正片', '{$playUrl}', 0, 1, datetime('now'), datetime('now'))");
            echo "视频 {$videoId}: 插入播放地址\n";
        }
    }
    
    echo "\n视频源更新完成！\n";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage() . "\n";
}