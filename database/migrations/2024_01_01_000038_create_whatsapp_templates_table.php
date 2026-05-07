<?php

use Core\Migration;

class CreateWhatsappTemplatesTable extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement("
            CREATE TABLE IF NOT EXISTS `whatsapp_templates` (
                `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`             VARCHAR(512) NOT NULL,
                `category`         ENUM('marketing','utility','service','authentication') NOT NULL,
                `language`         VARCHAR(10)  NOT NULL DEFAULT 'pt_BR',
                `header_type`      ENUM('none','text','image','video','document') NOT NULL DEFAULT 'none',
                `header_text`      TEXT         NULL,
                `body_text`        TEXT         NOT NULL,
                `footer_text`      VARCHAR(60)  NULL,
                `buttons`          JSON         NULL,
                `variables`        JSON         NULL,
                `status`           ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft',
                `meta_template_id` VARCHAR(100) NULL,
                `rejection_reason` TEXT         NULL,
                `created_at`       DATETIME     NULL,
                `updated_at`       DATETIME     NULL,
                PRIMARY KEY (`id`),
                KEY `idx_wt_category` (`category`),
                KEY `idx_wt_status`   (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        \Core\Database::getInstance()->statement("DROP TABLE IF EXISTS `whatsapp_templates`");
    }
}
