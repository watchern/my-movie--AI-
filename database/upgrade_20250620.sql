-- 2025-06-20 升级脚本
-- 为 collect_sources 表添加 description 字段

ALTER TABLE `collect_sources`
ADD COLUMN IF NOT EXISTS `description` VARCHAR(500) DEFAULT NULL COMMENT '资源描述' AFTER `name`;
