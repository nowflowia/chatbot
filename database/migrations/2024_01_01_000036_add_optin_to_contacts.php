<?php

use Core\Migration;

class AddOptinToContacts extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement("ALTER TABLE contacts ADD COLUMN opt_in_status   ENUM('pending','opted_in','opted_out') NOT NULL DEFAULT 'pending'");
        $db->statement("ALTER TABLE contacts ADD COLUMN opted_in_at     DATETIME NULL");
        $db->statement("ALTER TABLE contacts ADD COLUMN opted_out_at    DATETIME NULL");
        $db->statement("ALTER TABLE contacts ADD COLUMN opt_out_keyword VARCHAR(50) NULL");
        $db->statement("ALTER TABLE contacts ADD INDEX idx_contacts_optin (opt_in_status)");
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement("ALTER TABLE contacts DROP INDEX idx_contacts_optin");
        $db->statement("ALTER TABLE contacts DROP COLUMN opt_in_status");
        $db->statement("ALTER TABLE contacts DROP COLUMN opted_in_at");
        $db->statement("ALTER TABLE contacts DROP COLUMN opted_out_at");
        $db->statement("ALTER TABLE contacts DROP COLUMN opt_out_keyword");
    }
}
