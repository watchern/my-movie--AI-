-- =========================================
-- 影视系统数据库结构
-- 兼容 MySQL 5.7+ 和 SQLite 3.26+
-- =========================================

-- -----------------------------------------
-- 1. 用户表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
    `email` VARCHAR(100) NOT NULL COMMENT '邮箱',
    `password` VARCHAR(255) NOT NULL COMMENT '密码(加密)',
    `phone` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
    `vip_status` TINYINT(1) DEFAULT 0 COMMENT 'VIP状态: 0普通 1VIP',
    `vip_expire_time` DATETIME DEFAULT NULL COMMENT 'VIP过期时间',
    `total_watch_time` INT UNSIGNED DEFAULT 0 COMMENT '累计观看时长(分钟)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`)
);

-- 用户索引
CREATE INDEX IF NOT EXISTS `idx_users_phone` ON `users`(`phone`);

-- -----------------------------------------
-- 2. 影视分类表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '分类ID',
    `name` VARCHAR(50) NOT NULL COMMENT '分类名称',
    `slug` VARCHAR(50) NOT NULL COMMENT '别名(英文标识)',
    `parent_id` INT UNSIGNED DEFAULT 0 COMMENT '父级ID',
    `sort_order` INT UNSIGNED DEFAULT 100 COMMENT '排序',
    `type` TINYINT(1) DEFAULT 1 COMMENT '类型: 1电影 2电视剧 3动漫 4短视频 5纪录片',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

-- -----------------------------------------
-- 3. 影视内容表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `videos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '视频ID',
    `title` VARCHAR(255) NOT NULL COMMENT '标题',
    `source_vod_id` VARCHAR(50) DEFAULT NULL COMMENT '资源站原始vod_id(用于去重)',
    `category_id` INT UNSIGNED DEFAULT 0 COMMENT '分类ID',
    `type` TINYINT(1) DEFAULT 1 COMMENT '类型: 1电影 2电视剧 3动漫 4短视频 5纪录片',
    `tags` TEXT DEFAULT NULL COMMENT '标签(JSON数组)',
    `cover` VARCHAR(500) DEFAULT NULL COMMENT '封面图',
    `banner` VARCHAR(500) DEFAULT NULL COMMENT 'Banner图',
    `director` VARCHAR(255) DEFAULT NULL COMMENT '导演',
    `actors` TEXT DEFAULT NULL COMMENT '演员(JSON数组)',
    `description` TEXT DEFAULT NULL COMMENT '简介',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '时长(分钟)',
    `release_year` VARCHAR(10) DEFAULT NULL COMMENT '发行年份',
    `region` VARCHAR(50) DEFAULT NULL COMMENT '地区',
    `language` VARCHAR(50) DEFAULT NULL COMMENT '语言',
    `rating` DECIMAL(3,1) DEFAULT 0 COMMENT '评分',
    `play_count` INT UNSIGNED DEFAULT 0 COMMENT '播放次数',
    `is_vip` TINYINT(1) DEFAULT 0 COMMENT '是否VIP专享',
    `is_show` TINYINT(1) DEFAULT 1 COMMENT '是否显示',
    `is_deleted` TINYINT(1) DEFAULT 0 COMMENT '是否删除',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE INDEX IF NOT EXISTS `idx_videos_source_vod_id` ON `videos`(`source_vod_id`);

-- 影视索引
CREATE INDEX IF NOT EXISTS `idx_videos_type` ON `videos`(`type`);
CREATE INDEX IF NOT EXISTS `idx_videos_category` ON `videos`(`category_id`);
CREATE INDEX IF NOT EXISTS `idx_videos_is_vip` ON `videos`(`is_vip`);
CREATE UNIQUE INDEX IF NOT EXISTS `idx_videos_title_year` ON `videos`(`title`, `release_year`);

-- -----------------------------------------
-- 4. 视频资源播放地址表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `video_sources` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '资源ID',
    `video_id` INT UNSIGNED NOT NULL COMMENT '影视ID',
    `source_site_id` INT UNSIGNED NOT NULL COMMENT '资源站ID',
    `source_vid` VARCHAR(100) DEFAULT NULL COMMENT '资源站视频ID',
    `name` VARCHAR(255) DEFAULT NULL COMMENT '名称(如第1集、正片)',
    `sort_order` INT UNSIGNED DEFAULT 0 COMMENT '排序',
    `play_url` TEXT NOT NULL COMMENT '播放地址',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '时长(秒)',
    `is_vip` TINYINT(1) DEFAULT 0 COMMENT '是否VIP专享',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 0禁用 1正常',
    `last_sync_at` DATETIME DEFAULT NULL COMMENT '最后同步时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE INDEX IF NOT EXISTS `idx_video_sources_video` ON `video_sources`(`video_id`);
CREATE INDEX IF NOT EXISTS `idx_video_sources_site` ON `video_sources`(`source_site_id`);
CREATE INDEX IF NOT EXISTS `idx_video_sources_vid` ON `video_sources`(`source_vid`);
CREATE INDEX IF NOT EXISTS `idx_video_sources_sort` ON `video_sources`(`sort_order`);

-- -----------------------------------------
-- 5. 观看历史记录表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `watch_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `video_id` INT UNSIGNED NOT NULL COMMENT '影视ID',
    `video_source_id` INT UNSIGNED DEFAULT NULL COMMENT '视频资源ID',
    `progress` INT UNSIGNED DEFAULT 0 COMMENT '播放进度(秒)',
    `duration` INT UNSIGNED DEFAULT 0 COMMENT '总时长(秒)',
    `last_position` INT UNSIGNED DEFAULT 0 COMMENT '上次播放位置(秒)',
    `watched_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_video_source` (`user_id`, `video_id`, `video_source_id`)
);

CREATE INDEX IF NOT EXISTS `idx_history_user` ON `watch_history`(`user_id`);
CREATE INDEX IF NOT EXISTS `idx_history_video` ON `watch_history`(`video_id`);

-- -----------------------------------------
-- 6. 收藏表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `favorites` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '收藏ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `video_id` INT UNSIGNED NOT NULL COMMENT '影视ID',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_video` (`user_id`, `video_id`)
);

CREATE INDEX IF NOT EXISTS `idx_favorites_user` ON `favorites`(`user_id`);

-- -----------------------------------------
-- 7. 兑换码表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `card_keys` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '卡密ID',
    `code` VARCHAR(50) NOT NULL COMMENT '兑换码',
    `type` TINYINT(1) DEFAULT 1 COMMENT '类型: 1天卡 2周卡 3月卡 4季卡 5年卡 6永久',
    `days` INT UNSIGNED DEFAULT 1 COMMENT '天数',
    `price` DECIMAL(10,2) DEFAULT 0 COMMENT '面值',
    `status` TINYINT(1) DEFAULT 0 COMMENT '状态: 0未使用 1已使用 2已过期',
    `used_user_id` INT UNSIGNED DEFAULT NULL COMMENT '使用用户ID',
    `used_at` DATETIME DEFAULT NULL COMMENT '使用时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `expired_at` DATETIME DEFAULT NULL COMMENT '过期时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_code` (`code`)
);

CREATE INDEX IF NOT EXISTS `idx_card_status` ON `card_keys`(`status`);

-- -----------------------------------------
-- 8. VIP变动记录表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `vip_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '记录ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `type` VARCHAR(20) NOT NULL COMMENT '类型: card/ad/other',
    `sub_type` VARCHAR(50) DEFAULT NULL COMMENT '子类型',
    `days` INT DEFAULT 0 COMMENT '获得天数',
    `related_id` INT UNSIGNED DEFAULT NULL COMMENT '关联ID(卡密ID等)',
    `description` VARCHAR(255) DEFAULT NULL COMMENT '说明',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE INDEX IF NOT EXISTS `idx_vip_trans_user` ON `vip_transactions`(`user_id`);
CREATE INDEX IF NOT EXISTS `idx_vip_trans_type` ON `vip_transactions`(`type`);

-- -----------------------------------------
-- 9. 用户登录日志表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `login_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `login_ip` VARCHAR(50) DEFAULT NULL COMMENT '登录IP',
    `device` VARCHAR(20) DEFAULT NULL COMMENT '设备: mobile/tablet/desktop/other',
    `device_info` VARCHAR(255) DEFAULT NULL COMMENT '设备信息(UA)',
    `login_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE INDEX IF NOT EXISTS `idx_login_user` ON `login_logs`(`user_id`);
CREATE INDEX IF NOT EXISTS `idx_login_time` ON `login_logs`(`login_at`);

-- -----------------------------------------
-- 9.5 管理员登录日志表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `admin_login_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
    `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
    `login_ip` VARCHAR(50) DEFAULT NULL COMMENT '登录IP',
    `device` VARCHAR(20) DEFAULT NULL COMMENT '设备: mobile/tablet/desktop/other',
    `device_info` VARCHAR(500) DEFAULT NULL COMMENT '设备信息(UA)',
    `login_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE INDEX IF NOT EXISTS `idx_admin_login_admin` ON `admin_login_logs`(`admin_id`);
CREATE INDEX IF NOT EXISTS `idx_admin_login_time` ON `admin_login_logs`(`login_at`);

-- -----------------------------------------
-- 10. 资源站点配置表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `source_sites` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '站点ID',
    `name` VARCHAR(100) NOT NULL COMMENT '站点名称',
    `code` VARCHAR(50) NOT NULL COMMENT '站点代码',
    `api_url` VARCHAR(500) NOT NULL COMMENT 'API地址',
    `api_key` VARCHAR(255) DEFAULT NULL COMMENT 'API密钥',
    `is_vip` TINYINT(1) DEFAULT 0 COMMENT '是否VIP专享',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `sort_order` INT UNSIGNED DEFAULT 100 COMMENT '排序',
    `last_sync_at` DATETIME DEFAULT NULL COMMENT '最后同步时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE INDEX IF NOT EXISTS `idx_source_status` ON `source_sites`(`status`);

-- -----------------------------------------
-- 11. 系统配置表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `system_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '配置ID',
    `key` VARCHAR(100) NOT NULL COMMENT '配置键',
    `value` TEXT DEFAULT NULL COMMENT '配置值',
    `type` VARCHAR(50) DEFAULT 'string' COMMENT '类型: string/int/json/bool',
    `description` VARCHAR(255) DEFAULT NULL COMMENT '说明',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_key` (`key`)
);

-- 插入默认配置
INSERT IGNORE INTO `system_config` (`key`, `value`, `type`, `description`) VALUES
('site_name', '影视系统', 'string', '网站名称'),
('site_logo', '/static/logo.png', 'string', '网站Logo'),
('statistics_code', '', 'text', '统计代码'),
('ad_video_reward', '30', 'int', '看广告奖励时长(分钟)'),
('ad_daily_limit', '10', 'int', '每日广告观看次数上限'),
('default_vip_days', '0', 'int', '新用户注册赠送VIP天数');

-- -----------------------------------------
-- 12. 管理员表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '管理员ID',
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(255) NOT NULL COMMENT '密码(加密)',
    `nickname` VARCHAR(50) DEFAULT NULL COMMENT '昵称',
    `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `last_login_time` DATETIME DEFAULT NULL COMMENT '最后登录时间',
    `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录IP',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`)
);

-- 插入默认管理员账号 (admin / admin123)
-- 密码是 password_hash('admin123', PASSWORD_DEFAULT) 的结果
INSERT IGNORE INTO `admins` (`username`, `password`, `nickname`, `status`) VALUES
('admin', '$2y$10$tbW9zP3Tc3ts9rAvNzwlh.kG9WRxUD4IW3rXDarUDLGJ3scNS0rW6', '管理员', 1);

-- -----------------------------------------
-- 13. 管理员操作日志表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `admin_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
    `admin_id` INT UNSIGNED NOT NULL COMMENT '管理员ID',
    `type` VARCHAR(50) DEFAULT NULL COMMENT '操作类型',
    `detail` TEXT DEFAULT NULL COMMENT '操作详情',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT '操作IP',
    `device_info` TEXT DEFAULT NULL COMMENT '设备信息(UA)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE INDEX IF NOT EXISTS `idx_admin_logs_admin` ON `admin_logs`(`admin_id`);
CREATE INDEX IF NOT EXISTS `idx_admin_logs_type` ON `admin_logs`(`type`);
CREATE INDEX IF NOT EXISTS `idx_admin_logs_time` ON `admin_logs`(`created_at`);

-- -----------------------------------------
-- 14. 轮播图表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `banners` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'BannerID',
    `type` TINYINT(1) DEFAULT 1 COMMENT '类型: 1视频 2广告',
    `video_id` INT UNSIGNED DEFAULT 0 COMMENT '视频ID(类型为视频时)',
    `title` VARCHAR(255) DEFAULT NULL COMMENT '标题(广告时使用)',
    `image_url` VARCHAR(500) DEFAULT NULL COMMENT '图片URL(广告时使用)',
    `link_url` VARCHAR(500) DEFAULT NULL COMMENT '跳转链接(广告时使用)',
    `sort_order` INT UNSIGNED DEFAULT 100 COMMENT '排序',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `expire_at` DATETIME DEFAULT NULL COMMENT '到期时间(广告时使用，NULL表示永不过期)',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE INDEX IF NOT EXISTS `idx_banners_type` ON `banners`(`type`);
CREATE INDEX IF NOT EXISTS `idx_banners_status` ON `banners`(`status`);
CREATE INDEX IF NOT EXISTS `idx_banners_sort` ON `banners`(`sort_order`);

-- -----------------------------------------
-- 15. 资源采集站点表
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `collect_sources` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '采集站ID',
    `name` VARCHAR(100) NOT NULL COMMENT '站点名称',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '资源描述',
    `api_url` VARCHAR(500) NOT NULL COMMENT 'API地址',
    `site_type` TINYINT(1) DEFAULT 1 COMMENT '站点类型: 1苹果CMS 2其他',
    `status` TINYINT(1) DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    `page_count` INT UNSIGNED DEFAULT 0 COMMENT '资源站总页数（测试连接时获取）',
    `last_collected_page` INT UNSIGNED DEFAULT 0 COMMENT '最后一次采集到的页码（断点续采）',
    `last_collected_vod_id` VARCHAR(50) DEFAULT NULL COMMENT '最后一次采集的视频ID',
    `last_collected_vod_name` VARCHAR(255) DEFAULT NULL COMMENT '最后一次采集的视频名称',
    `last_collected_vod_pic` VARCHAR(500) DEFAULT NULL COMMENT '最后一次采集的视频封面',
    `last_collected_vod_year` VARCHAR(10) DEFAULT NULL COMMENT '最后一次采集的视频年份',
    `last_collected_vod_score` DECIMAL(3,1) DEFAULT 0 COMMENT '最后一次采集的视频评分',
    `last_collected_vod_duration` INT DEFAULT 0 COMMENT '最后一次采集的视频时长',
    `last_collected_at` DATETIME DEFAULT NULL COMMENT '最后一次采集时间',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE INDEX IF NOT EXISTS `idx_collect_sources_status` ON `collect_sources`(`status`);
