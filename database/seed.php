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
        // 电影 (type=1)
        ['title' => '流浪地球2', 'type' => 1, 'cover' => 'https://placehold.co/400x600/1a1a2e/white?text=Movie+1', 'rating' => 8.5, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '郭帆', 'description' => '太阳即将毁灭，人类在地球表面建造出巨大的推进器，寻找新的家园。'],
        ['title' => '满江红', 'type' => 1, 'cover' => 'https://placehold.co/400x600/16213e/white?text=Movie+2', 'rating' => 7.8, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '张艺谋', 'description' => '南宋绍兴年间，秦桧率兵与金国会谈。'],
        ['title' => '孤注一掷', 'type' => 1, 'cover' => 'https://placehold.co/400x600/2d4059/white?text=Movie+3', 'rating' => 7.9, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '申奥', 'description' => '程序员潘生被骗到境外赌场，展开了一场惊心动魄的逃亡。'],
        ['title' => '八角笼中', 'type' => 1, 'cover' => 'https://placehold.co/400x600/3d5a80/white?text=Movie+4', 'rating' => 8.2, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '王宝强', 'description' => '向腾辉倾注心血，训练大山里的孩子们练习综合格斗。'],
        ['title' => '消失的她', 'type' => 1, 'cover' => 'https://placehold.co/400x600/4a4e69/white?text=Movie+5', 'rating' => 8.0, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '崔睿', 'description' => '何非的妻子在东南亚失踪，留下一个谜团。'],
        ['title' => '封神第一部', 'type' => 1, 'cover' => 'https://placehold.co/400x600/5c6b73/white?text=Movie+6', 'rating' => 7.8, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '乌尔善', 'description' => '殷商末年，纣王无道，昆仑仙人欲救苍生于水火。'],
        ['title' => '长安三万里', 'type' => 1, 'cover' => 'https://placehold.co/400x600/6c757d/white?text=Movie+7', 'rating' => 8.3, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '谢君伟', 'description' => '大唐盛世，无数诗人用诗歌书写他们的故事。'],
        ['title' => '奥本海默', 'type' => 1, 'cover' => 'https://placehold.co/400x600/7c8591/white?text=Movie+8', 'rating' => 8.9, 'region' => '美国', 'release_year' => '2023', 'director' => '克里斯托弗·诺兰', 'description' => '原子弹之父奥本海默的传奇人生。'],
        
        // 电视剧 (type=2)
        ['title' => '狂飙', 'type' => 2, 'cover' => 'https://placehold.co/400x600/0f3460/white?text=TV+Drama+1', 'rating' => 9.1, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '徐纪周', 'description' => '京海市一线刑警安欣，在与黑恶势力的斗争中，不断遭到保护伞的打击。'],
        ['title' => '漫长的季节', 'type' => 2, 'cover' => 'https://placehold.co/400x600/533483/white?text=TV+Drama+2', 'rating' => 9.2, 'region' => '中国大陆', 'release_year' => '2023', 'director' => '辛爽', 'description' => '小城桦林，一家人的命运在时代洪流中翻天覆地。'],
        ['title' => '繁花', 'type' => 2, 'cover' => 'https://placehold.co/400x600/6b4984/white?text=TV+Drama+3', 'rating' => 8.7, 'region' => '中国大陆', 'release_year' => '2024', 'director' => '王家卫', 'description' => '九十年代的上海，阿宝在商海中沉浮。'],
        ['title' => '追风者', 'type' => 2, 'cover' => 'https://placehold.co/400x600/7b68a0/white?text=TV+Drama+4', 'rating' => 8.5, 'region' => '中国大陆', 'release_year' => '2024', 'director' => '姚晓峰', 'description' => '1934年江西，红色金融崛起的热血故事。'],
        ['title' => '庆余年2', 'type' => 2, 'cover' => 'https://placehold.co/400x600/8b7ab8/white?text=TV+Drama+5', 'rating' => 8.3, 'region' => '中国大陆', 'release_year' => '2024', 'director' => '孙浩', 'description' => '范闲继续在朝堂和江湖中书写传奇。'],
        ['title' => '琅琊榜', 'type' => 2, 'cover' => 'https://placehold.co/400x600/9b8cc0/white?text=TV+Drama+6', 'rating' => 9.0, 'region' => '中国大陆', 'release_year' => '2015', 'director' => '孔笙', 'description' => '梅长苏以病弱之躯拨开重重迷雾，为昭雪冤案。'],
        ['title' => '甄嬛传', 'type' => 2, 'cover' => 'https://placehold.co/400x600/ab9dc8/white?text=TV+Drama+7', 'rating' => 9.1, 'region' => '中国大陆', 'release_year' => '2011', 'director' => '郑晓龙', 'description' => '雍正年间，后宫争斗的传奇故事。'],
        ['title' => '我的前半生', 'type' => 2, 'cover' => 'https://placehold.co/400x600/bBAED0/white?text=TV+Drama+8', 'rating' => 7.8, 'region' => '中国大陆', 'release_year' => '2017', 'director' => '沈严', 'description' => '子君的婚姻触礁，开始新的人生。'],
        
        // 动漫 (type=3)
        ['title' => '进击的巨人 最终季', 'type' => 3, 'cover' => 'https://placehold.co/400x600/e94560/white?text=Anime+1', 'rating' => 9.5, 'region' => '日本', 'release_year' => '2023', 'director' => '林祐一郎', 'description' => '讲述人类与巨人的生死之战。'],
        ['title' => '鬼灭之刃 刀匠村篇', 'type' => 3, 'cover' => 'https://placehold.co/400x600/ff6b6b/white?text=Anime+2', 'rating' => 8.8, 'region' => '日本', 'release_year' => '2023', 'director' => '外崎春雄', 'description' => '炭治郎为了救回祢豆子，踏上新的旅程。'],
        ['title' => '咒术回战 第二季', 'type' => 3, 'cover' => 'https://placehold.co/400x600/f38181/white?text=Anime+3', 'rating' => 9.2, 'region' => '日本', 'release_year' => '2023', 'director' => '朴性厚', 'description' => '五条悟与宿傩的涩谷事变。'],
        ['title' => '排球少年!! 垃圾场决战', 'type' => 3, 'cover' => 'https://placehold.co/400x600/fce38a/white?text=Anime+4', 'rating' => 9.0, 'region' => '日本', 'release_year' => '2024', 'director' => '满仲劝', 'description' => '乌野高中与音驹高中的激烈对决。'],
        ['title' => '间谍过家家 第二季', 'type' => 3, 'cover' => 'https://placehold.co/400x600/a8d8ea/white?text=Anime+5', 'rating' => 9.1, 'region' => '日本', 'release_year' => '2023', 'director' => '古桥一浩', 'description' => '伪装家庭的新日常。'],
        ['title' => '赛马娘 第三季', 'type' => 3, 'cover' => 'https://placehold.co/400x600/96c6ff/white?text=Anime+6', 'rating' => 8.7, 'region' => '日本', 'release_year' => '2023', 'director' => '于地纮仁', 'description' => '赛马娘们追逐梦想的故事。'],
        ['title' => '蓝色监狱', 'type' => 3, 'cover' => 'https://placehold.co/400x600/74c0fc/white?text=Anime+7', 'rating' => 8.9, 'region' => '日本', 'release_year' => '2022', 'director' => '渡边彻宇', 'description' => '300名前锋的生存争夺战。'],
        ['title' => '国王排名', 'type' => 3, 'cover' => 'https://placehold.co/400x600/63e6be/white?text=Anime+8', 'rating' => 8.5, 'region' => '日本', 'release_year' => '2021', 'director' => '八田洋介', 'description' => '小王子波吉的冒险故事。'],
        
        // 短视频 (type=4)
        ['title' => '搞笑短视频合集', 'type' => 4, 'cover' => 'https://placehold.co/400x600/4ecdc4/white?text=Short+1', 'rating' => 7.5, 'region' => '中国大陆', 'release_year' => '2024', 'director' => 'Various', 'description' => '各种搞笑短视频精彩合集'],
        ['title' => '萌宠日常', 'type' => 4, 'cover' => 'https://placehold.co/400x600/20c997/white?text=Short+2', 'rating' => 8.0, 'region' => '中国大陆', 'release_year' => '2024', 'director' => 'Various', 'description' => '可爱宠物的日常记录'],
        ['title' => '美食教程', 'type' => 4, 'cover' => 'https://placehold.co/400x600/12b19d/white?text=Short+3', 'rating' => 7.8, 'region' => '中国大陆', 'release_year' => '2024', 'director' => 'Various', 'description' => '简单易学的美食制作教程'],
        ['title' => '生活小妙招', 'type' => 4, 'cover' => 'https://placehold.co/400x600/0ca678/white?text=Short+4', 'rating' => 7.6, 'region' => '中国大陆', 'release_year' => '2024', 'director' => 'Various', 'description' => '实用生活技巧大放送'],
        
        // 纪录片 (type=5)
        ['title' => '宇宙探索纪录片', 'type' => 5, 'cover' => 'https://placehold.co/400x600/45b7d1/white?text=Doc+1', 'rating' => 9.0, 'region' => '美国', 'release_year' => '2023', 'director' => 'various', 'description' => '探索宇宙奥秘的纪录片'],
        ['title' => '舌尖上的中国', 'type' => 5, 'cover' => 'https://placehold.co/400x600/17a2b8/white?text=Doc+2', 'rating' => 9.3, 'region' => '中国大陆', 'release_year' => '2022', 'director' => '刘文', 'description' => '中国各地美食文化探索'],
        ['title' => '地球脉动 第三季', 'type' => 5, 'cover' => 'https://placehold.co/400x600/6610f2/white?text=Doc+3', 'rating' => 9.5, 'region' => '英国', 'release_year' => '2023', 'director' => '大卫·爱登堡', 'description' => '记录地球上最惊人的自然奇观'],
        ['title' => '如果历史是一群喵', 'type' => 5, 'cover' => 'https://placehold.co/400x600/6f42c1/white?text=Doc+4', 'rating' => 8.2, 'region' => '中国大陆', 'release_year' => '2024', 'director' => '肥志', 'description' => '用猫咪演绎中国历史'],
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
                $playUrl = $i % 2 == 0 
                    ? 'https://www.w3schools.com/html/mov_bbb.mp4' 
                    : 'https://www.w3schools.com/html/movie.mp4';
                $pdo->exec("INSERT INTO video_sources (video_id, source_site_id, name, play_url, sort_order, status, created_at, updated_at) VALUES ({$videoId}, 1, '第{$i}集', '{$playUrl}', {$i}, 1, datetime('now'), datetime('now'))");
            }
            echo "  -> 插入 {$episodeCount} 个剧集\n";
        } else {
            // 电影/短视频插入一个播放地址
            $playUrls = [
                'https://www.w3schools.com/html/mov_bbb.mp4',
                'https://www.w3schools.com/html/movie.mp4',
            ];
            $playUrl = $playUrls[rand(0, 1)];
            $pdo->exec("INSERT INTO video_sources (video_id, source_site_id, name, play_url, sort_order, status, created_at, updated_at) VALUES ({$videoId}, 1, '正片', '{$playUrl}', 0, 1, datetime('now'), datetime('now'))");
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
