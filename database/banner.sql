-- 轮播图表
CREATE TABLE IF NOT EXISTS `banners` (
    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `type` TINYINT(1) DEFAULT 1 COMMENT '类型: 1视频 2广告',
    `video_id` INTEGER DEFAULT 0 COMMENT '视频ID(类型为视频时)',
    `title` VARCHAR(255) DEFAULT NULL COMMENT '标题(广告时使用)',
    `image_url` VARCHAR(500) DEFAULT NULL COMMENT '图片URL(广告时使用)',
    `link_url` VARCHAR(500) DEFAULT NULL COMMENT '跳转链接(广告时使用)',
    `sort_order` INTEGER DEFAULT 100 COMMENT '排序',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS `idx_banners_type` ON `banners`(`type`);
CREATE INDEX IF NOT EXISTS `idx_banners_status` ON `banners`(`status`);
CREATE INDEX IF NOT EXISTS `idx_banners_sort` ON `banners`(`sort_order`);