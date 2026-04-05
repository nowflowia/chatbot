<?php

use Core\Migration;

class AddWebhookLoggingToCompanySettings extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement("ALTER TABLE company_settings ADD COLUMN IF NOT EXISTS webhook_logging TINYINT(1) NOT NULL DEFAULT 1");
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement("ALTER TABLE company_settings DROP COLUMN IF EXISTS webhook_logging");
    }
}
