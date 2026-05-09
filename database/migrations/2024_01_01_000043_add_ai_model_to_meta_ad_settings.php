<?php

use Core\Migration;

class AddAiModelToMetaAdSettings extends Migration
{
    public function up(): void
    {
        \Core\Database::getInstance()->statement("
            ALTER TABLE `meta_ad_settings`
            ADD COLUMN `ai_model` VARCHAR(80) NULL DEFAULT 'claude-sonnet-4-6'
            AFTER `api_version`
        ");
    }

    public function down(): void
    {
        \Core\Database::getInstance()->statement("
            ALTER TABLE `meta_ad_settings` DROP COLUMN `ai_model`
        ");
    }
}
