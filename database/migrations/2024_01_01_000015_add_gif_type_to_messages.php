<?php

use Core\Migration;

class AddGifTypeToMessages extends Migration
{
    public function up(): void
    {
        \Core\Database::getInstance()->select(
            "ALTER TABLE messages MODIFY COLUMN type ENUM(
                'text','image','audio','video','gif','document',
                'location','sticker','reaction','template','interactive','system'
             ) NOT NULL DEFAULT 'text'"
        );
    }

    public function down(): void
    {
        \Core\Database::getInstance()->select(
            "ALTER TABLE messages MODIFY COLUMN type ENUM(
                'text','image','audio','video','document',
                'location','sticker','reaction','template','interactive','system'
             ) NOT NULL DEFAULT 'text'"
        );
    }
}
