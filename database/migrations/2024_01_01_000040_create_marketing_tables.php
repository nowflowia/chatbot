<?php

use Core\Migration;

class CreateMarketingTables extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();

        $db->statement("
            CREATE TABLE IF NOT EXISTS `marketing_lists` (
                `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`        VARCHAR(120) NOT NULL,
                `description` TEXT         NULL,
                `created_at`  DATETIME     NULL,
                `updated_at`  DATETIME     NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ml_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->statement("
            CREATE TABLE IF NOT EXISTS `marketing_list_contacts` (
                `list_id`    INT UNSIGNED NOT NULL,
                `contact_id` INT UNSIGNED NOT NULL,
                `added_at`   DATETIME     NULL,
                PRIMARY KEY (`list_id`, `contact_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->statement("
            CREATE TABLE IF NOT EXISTS `marketing_campaigns` (
                `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`                 VARCHAR(120) NOT NULL,
                `whatsapp_template_id` INT UNSIGNED NULL,
                `list_id`              INT UNSIGNED NULL,
                `variables`            JSON         NULL,
                `status`               ENUM('draft','scheduled','sending','sent','cancelled') NOT NULL DEFAULT 'draft',
                `scheduled_at`         DATETIME     NULL,
                `started_at`           DATETIME     NULL,
                `finished_at`          DATETIME     NULL,
                `total_contacts`       INT UNSIGNED NOT NULL DEFAULT 0,
                `sent_count`           INT UNSIGNED NOT NULL DEFAULT 0,
                `failed_count`         INT UNSIGNED NOT NULL DEFAULT 0,
                `created_by`           INT UNSIGNED NULL,
                `created_at`           DATETIME     NULL,
                `updated_at`           DATETIME     NULL,
                PRIMARY KEY (`id`),
                KEY `idx_mc_status` (`status`),
                KEY `idx_mc_list`   (`list_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement("DROP TABLE IF EXISTS `marketing_campaigns`");
        $db->statement("DROP TABLE IF EXISTS `marketing_list_contacts`");
        $db->statement("DROP TABLE IF EXISTS `marketing_lists`");
    }
}
