-- 影视系统数据库初始化脚本

-- 管理员表
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '密码',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='管理员表';

INSERT INTO `admins` (`username`, `password`) VALUES ('admin', '');

-- 系统配置表
CREATE TABLE IF NOT EXISTS `system_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '配置键',
  `value` TEXT COMMENT '配置值',
  `group` VARCHAR(50) DEFAULT 'basic' COMMENT '配置分组',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='系统配置表';

INSERT INTO `system_config` (`key`, `value`, `group`) VALUES 
('site_name', '影视系统', 'basic'),
('site_keywords', '视频,电影,电视剧,动漫', 'basic'),
('site_description', '一个优秀的视频网站', 'basic');

-- 用户表
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `username` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '密码',
  `avatar` VARCHAR(255) DEFAULT '' COMMENT '头像',
  `vip_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'VIP状态 0:普通 1:VIP',
  `vip_expire_time` DATETIME DEFAULT NULL COMMENT 'VIP到期时间',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户表';

-- 分类表
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `slug` VARCHAR(50) DEFAULT '' COMMENT '英文标识',
  `type` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '类型 1:电影 2:电视剧 3:动漫 4:短视频 5:纪录片',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='分类表';

INSERT INTO `categories` (`name`, `slug`, `type`, `sort_order`) VALUES 
('电影', 'movie', 1, 1),
('电视剧', 'tv', 2, 2),
('动漫', 'anime', 3, 3),
('短视频', 'short', 4, 4),
('纪录片', 'documentary', 5, 5);

-- 视频表
CREATE TABLE IF NOT EXISTS `videos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED DEFAULT 0 COMMENT '分类ID',
  `title` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '标题',
  `cover` VARCHAR(255) DEFAULT '' COMMENT '封面图',
  `banner` VARCHAR(255) DEFAULT '' COMMENT 'Banner图',
  `type` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '类型 1:电影 2:电视剧 3:动漫 4:短视频 5:纪录片',
  `tags` VARCHAR(255) DEFAULT '' COMMENT '标签，多个用逗号分隔',
  `director` VARCHAR(100) DEFAULT '' COMMENT '导演',
  `actors` TEXT COMMENT '演员，JSON数组',
  `release_year` INT DEFAULT NULL COMMENT '上映年份',
  `region` VARCHAR(50) DEFAULT '' COMMENT '地区',
  `language` VARCHAR(50) DEFAULT '' COMMENT '语言',
  `duration` INT DEFAULT 0 COMMENT '时长(分钟)',
  `rating` DECIMAL(3,1) DEFAULT 0 COMMENT '评分',
  `play_count` INT UNSIGNED DEFAULT 0 COMMENT '播放次数',
  `is_vip` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否VIP专属 0:否 1:是',
  `is_show` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否显示 0:隐藏 1:显示',
  `description` TEXT COMMENT '简介',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_category` (`category_id`),
  KEY `idx_is_show` (`is_show`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='视频表';

-- 视频资源表(剧集)
CREATE TABLE IF NOT EXISTS `video_sources` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `video_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '视频ID',
  `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '集数标题',
  `episode` INT NOT NULL DEFAULT 1 COMMENT '集数',
  `url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '播放地址',
  `duration` INT DEFAULT 0 COMMENT '时长(秒)',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_video_id` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='视频资源表';

-- 收藏表
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `video_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '视频ID',
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_video` (`user_id`, `video_id`),
  UNIQUE KEY `uk_user_video` (`user_id`, `video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='收藏表';

-- 观看历史表
CREATE TABLE IF NOT EXISTS `watch_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `video_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '视频ID',
  `video_source_id` INT UNSIGNED DEFAULT 0 COMMENT '资源ID(剧集)',
  `episode_name` VARCHAR(100) DEFAULT '' COMMENT '选集名称',
  `progress` INT DEFAULT 0 COMMENT '观看进度(秒)',
  `duration` INT DEFAULT 0 COMMENT '总时长(秒)',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_user_video` (`user_id`, `video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='观看历史表';

-- VIP交易记录表
CREATE TABLE IF NOT EXISTS `vip_transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `card_id` INT UNSIGNED DEFAULT 0 COMMENT '卡密ID',
  `days` INT NOT NULL DEFAULT 0 COMMENT '充值天数',
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='VIP交易记录表';

-- 卡密表
CREATE TABLE IF NOT EXISTS `card_keys` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `card_key` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '卡密',
  `days` INT NOT NULL DEFAULT 0 COMMENT '天数',
  `user_id` INT UNSIGNED DEFAULT 0 COMMENT '使用用户ID',
  `used` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否使用 0:否 1:是',
  `created_at` DATETIME DEFAULT NULL,
  `used_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_card_key` (`card_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='卡密表';

-- 登录日志表
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT 0 COMMENT '用户ID',
  `email` VARCHAR(100) DEFAULT '' COMMENT '邮箱',
  `ip` VARCHAR(50) DEFAULT '' COMMENT 'IP地址',
  `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User Agent',
  `status` TINYINT(1) DEFAULT 0 COMMENT '登录状态 0:失败 1:成功',
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='登录日志表';

-- 管理员操作日志表
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID',
  `action` VARCHAR(100) DEFAULT '' COMMENT '操作',
  `detail` TEXT COMMENT '详情',
  `ip` VARCHAR(50) DEFAULT '' COMMENT 'IP地址',
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='管理员操作日志表';

-- 管理员登录日志表
CREATE TABLE IF NOT EXISTS `admin_login_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED DEFAULT 0 COMMENT '管理员ID',
  `username` VARCHAR(50) DEFAULT '' COMMENT '用户名',
  `ip` VARCHAR(50) DEFAULT '' COMMENT 'IP地址',
  `user_agent` VARCHAR(500) DEFAULT '' COMMENT 'User Agent',
  `status` TINYINT(1) DEFAULT 0 COMMENT '登录状态 0:失败 1:成功',
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='管理员登录日志表';

-- 轮播图表
CREATE TABLE IF NOT EXISTS `banners` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '类型 1:视频 2:广告',
  `video_id` INT UNSIGNED DEFAULT 0 COMMENT '视频ID',
  `title` VARCHAR(200) DEFAULT '' COMMENT '标题',
  `image_url` VARCHAR(255) DEFAULT '' COMMENT '图片URL',
  `link_url` VARCHAR(500) DEFAULT '' COMMENT '跳转链接',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态 0:禁用 1:启用',
  `expire_at` DATETIME DEFAULT NULL COMMENT '到期时间',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='轮播图表';

-- 资源采集站点表
CREATE TABLE IF NOT EXISTS `collect_sources` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '站点名称',
  `api_url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '接口地址',
  `site_type` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '站点类型 1:苹果CMS 2:其他',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态 0:禁用 1:启用',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='资源采集站点表';

-- 资源站点表(视频来源)
CREATE TABLE IF NOT EXISTS `source_sites` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '站点名称(前台展示)',
  `description` TEXT COMMENT '资源描述',
  `url` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '站点URL',
  `api_key` VARCHAR(100) DEFAULT '' COMMENT 'API密钥',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态 0:禁用 1:启用',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='资源站点表';
