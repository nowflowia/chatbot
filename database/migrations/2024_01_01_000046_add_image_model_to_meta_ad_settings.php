<?php

use Core\Migration;

class AddImageModelToMetaAdSettings extends Migration
{
    public function up(): void
    {
        \Core\Database::getInstance()->statement("
            ALTER TABLE `meta_ad_settings`
            ADD COLUMN `image_model` VARCHAR(80) NULL DEFAULT 'gpt-image-1'
            AFTER `agent_persona`
        ");
    }

    public function down(): void
    {
        \Core\Database::getInstance()->statement("
            ALTER TABLE `meta_ad_settings` DROP COLUMN `image_model`
        ");
    }
}
