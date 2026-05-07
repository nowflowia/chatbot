<?php

use Core\Migration;

class AddAiTypeToFlowNodes extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement(
            "ALTER TABLE flow_nodes MODIFY COLUMN type
             ENUM('message','list','transfer','question','flow_ia','wait','finish','condition','api_call','ai')
             NOT NULL DEFAULT 'message'"
        );
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement(
            "ALTER TABLE flow_nodes MODIFY COLUMN type
             ENUM('message','list','transfer','question','flow_ia','wait','finish','condition','api_call')
             NOT NULL DEFAULT 'message'"
        );
    }
}
