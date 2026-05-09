<?php

use Core\Migration;

class AddAgentPersonaToMetaAdSettings extends Migration
{
    public function up(): void
    {
        \Core\Database::getInstance()->statement("
            ALTER TABLE `meta_ad_settings`
            ADD COLUMN `agent_persona` LONGTEXT NULL
            AFTER `ai_model`
        ");
    }

    public function down(): void
    {
        \Core\Database::getInstance()->statement("
            ALTER TABLE `meta_ad_settings` DROP COLUMN `agent_persona`
        ");
    }
}
