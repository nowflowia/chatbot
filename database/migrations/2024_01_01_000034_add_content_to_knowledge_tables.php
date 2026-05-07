<?php

use Core\Migration;

class AddContentToKnowledgeTables extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement("ALTER TABLE ai_knowledge_docs  ADD COLUMN extracted_text LONGTEXT NULL AFTER size_bytes");
        $db->statement("ALTER TABLE ai_knowledge_sites ADD COLUMN content        LONGTEXT NULL AFTER title");
    }

    public function down(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement("ALTER TABLE ai_knowledge_docs  DROP COLUMN extracted_text");
        $db->statement("ALTER TABLE ai_knowledge_sites DROP COLUMN content");
    }
}
