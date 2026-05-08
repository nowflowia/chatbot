<?php

use Core\Migration;

class CreateInstagramTables extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();

        $db->statement("
            CREATE TABLE IF NOT EXISTS `instagram_settings` (
                `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`                 VARCHAR(80)  NOT NULL DEFAULT 'Principal',
                `app_id`               VARCHAR(100) NULL,
                `app_secret`           VARCHAR(255) NULL,
                `access_token`         TEXT         NULL,
                `instagram_account_id` VARCHAR(100) NULL,
                `page_id`              VARCHAR(100) NULL,
                `webhook_verify_token` VARCHAR(120) NULL,
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
            CREATE TABLE IF NOT EXISTS `instagram_posts` (
                `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `campaign_name`       VARCHAR(120) NULL,
                `media_type`          ENUM('IMAGE','VIDEO','CAROUSEL','REELS','STORIES') NOT NULL DEFAULT 'IMAGE',
                `caption`             TEXT         NULL,
                `media_urls`          JSON         NULL,
                `hashtags`            TEXT         NULL,
                `status`              ENUM('draft','scheduled','publishing','published','failed') NOT NULL DEFAULT 'draft',
                `scheduled_at`        DATETIME     NULL,
                `published_at`        DATETIME     NULL,
                `instagram_post_id`   VARCHAR(100) NULL,
                `permalink`           VARCHAR(512) NULL,
                `error_message`       TEXT         NULL,
                `created_by`          INT UNSIGNED NULL,
                `created_at`          DATETIME     NULL,
                `updated_at`          DATETIME     NULL,
                PRIMARY KEY (`id`),
                KEY `idx_igp_status`  (`status`),
                KEY `idx_igp_sched`   (`scheduled_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement("DROP TABLE IF EXISTS `instagram_posts`");
        $db->statement("DROP TABLE IF EXISTS `instagram_settings`");
    }
}
