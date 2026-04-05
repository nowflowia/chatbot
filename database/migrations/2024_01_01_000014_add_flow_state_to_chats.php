<?php

use Core\Migration;

class AddFlowStateToChats extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        $db->select("ALTER TABLE chats
            ADD COLUMN IF NOT EXISTS current_node_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER flow_id,
            ADD COLUMN IF NOT EXISTS flow_vars       LONGTEXT          NULL DEFAULT NULL AFTER current_node_id"
        );
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        $db->select("ALTER TABLE chats
            DROP COLUMN IF EXISTS current_node_id,
            DROP COLUMN IF EXISTS flow_vars"
        );
    }
}
