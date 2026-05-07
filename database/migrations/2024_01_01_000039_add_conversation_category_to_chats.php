<?php

use Core\Migration;

class AddConversationCategoryToChats extends Migration
{
    public function up(): void
    {
        $db = \Core\Database::getInstance();
        $db->statement("
            ALTER TABLE `chats`
            ADD COLUMN `conversation_category`
                ENUM('service','marketing','utility','authentication') NULL DEFAULT NULL
            AFTER `status`
        ");
    }

    public function down(): void
    {
        \Core\Database::getInstance()->statement("
            ALTER TABLE `chats` DROP COLUMN `conversation_category`
        ");
    }
}
