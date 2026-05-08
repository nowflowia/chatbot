<?php

use Core\Migration;

class CreateMetaAdsTables extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();

        $db->statement("
            CREATE TABLE IF NOT EXISTS `meta_ad_settings` (
                `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `app_id`               VARCHAR(100) NULL,
                `app_secret`           VARCHAR(255) NULL,
                `access_token`         TEXT         NULL,
                `ad_account_id`        VARCHAR(60)  NULL COMMENT 'act_XXXXXXXXX',
                `business_id`          VARCHAR(60)  NULL,
                `page_id`              VARCHAR(60)  NULL COMMENT 'Facebook Page ID',
                `instagram_actor_id`   VARCHAR(60)  NULL COMMENT 'IG account for ads',
                `api_version`          VARCHAR(10)  NOT NULL DEFAULT 'v21.0',
                `status`               ENUM('active','inactive','error') NOT NULL DEFAULT 'inactive',
                `last_error`           TEXT         NULL,
                `last_tested_at`       DATETIME     NULL,
                `created_at`           DATETIME     NULL,
                `updated_at`           DATETIME     NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->statement("
            CREATE TABLE IF NOT EXISTS `meta_campaigns` (
                `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`             VARCHAR(150) NOT NULL,
                `objective`        VARCHAR(60)  NOT NULL DEFAULT 'OUTCOME_TRAFFIC',
                `platforms`        JSON         NULL COMMENT '[\"facebook\",\"instagram\"]',
                `status`           ENUM('draft','pending_approval','active','paused','completed','cancelled') NOT NULL DEFAULT 'draft',
                `budget_type`      ENUM('daily','lifetime') NOT NULL DEFAULT 'daily',
                `budget_amount`    DECIMAL(10,2) NULL,
                `start_date`       DATE         NULL,
                `end_date`         DATE         NULL,
                `target_audience`  JSON         NULL,
                `ad_copy`          JSON         NULL COMMENT '{headline,body,cta,image_url}',
                `strategy_brief`   TEXT         NULL,
                `meta_campaign_id` VARCHAR(60)  NULL,
                `meta_adset_id`    VARCHAR(60)  NULL,
                `meta_ad_id`       VARCHAR(60)  NULL,
                `insights`         JSON         NULL,
                `insights_at`      DATETIME     NULL,
                `created_by`       INT UNSIGNED NULL,
                `approved_by`      INT UNSIGNED NULL,
                `approved_at`      DATETIME     NULL,
                `created_at`       DATETIME     NULL,
                `updated_at`       DATETIME     NULL,
                PRIMARY KEY (`id`),
                KEY `idx_mc_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->statement("
            CREATE TABLE IF NOT EXISTS `meta_agent_sessions` (
                `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `campaign_id` INT UNSIGNED NULL,
                `title`       VARCHAR(120) NULL,
                `messages`    LONGTEXT     NULL COMMENT 'JSON array of {role,content,actions?}',
                `status`      ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
                `created_by`  INT UNSIGNED NULL,
                `created_at`  DATETIME     NULL,
                `updated_at`  DATETIME     NULL,
                PRIMARY KEY (`id`),
                KEY `idx_mas_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement("DROP TABLE IF EXISTS `meta_agent_sessions`");
        $db->statement("DROP TABLE IF EXISTS `meta_campaigns`");
        $db->statement("DROP TABLE IF EXISTS `meta_ad_settings`");
    }
}
