<?php

use Core\Migration;

class CreateMetaAgentKnowledge extends Migration
{
    public function up(): void
    {
        \Core\Database::getInstance()->statement("
            CREATE TABLE IF NOT EXISTS `meta_agent_knowledge` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `session_id` INT UNSIGNED NOT NULL,
                `kind`       ENUM('url','document') NOT NULL DEFAULT 'document',
                `label`      VARCHAR(255) NULL,
                `source`     VARCHAR(500) NULL COMMENT 'URL or original filename',
                `file_path`  VARCHAR(500) NULL COMMENT 'Stored upload path (documents only)',
                `mime_type`  VARCHAR(120) NULL,
                `size_bytes` INT UNSIGNED NULL,
                `content`    LONGTEXT     NULL COMMENT 'Extracted plain text',
                `created_at` DATETIME     NULL,
                PRIMARY KEY (`id`),
                KEY `idx_mak_session` (`session_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        \Core\Database::getInstance()->statement("DROP TABLE IF EXISTS `meta_agent_knowledge`");
    }
}
