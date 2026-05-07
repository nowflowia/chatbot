<?php

use Core\Migration;

class AddStyleToAiPersona extends Migration
{
    public function up(): void
    {
        \Core\Database::getInstance()->statement(
            "ALTER TABLE ai_persona ADD COLUMN style VARCHAR(32) NOT NULL DEFAULT 'profissional' AFTER prompt"
        );
    }

    public function down(): void
    {
        \Core\Database::getInstance()->statement(
            "ALTER TABLE ai_persona DROP COLUMN style"
        );
    }
}
