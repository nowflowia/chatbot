<?php

use Core\Migration;
use Core\Blueprint;

class CreateChatsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id')->notNull();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('flow_id')->nullable();
            $table->unsignedBigInteger('whatsapp_setting_id')->nullable();
            $table->enum('status', ['waiting', 'in_progress', 'finished', 'bot'])->notNull()->default('waiting');
            $table->enum('channel', ['whatsapp', 'api'])->notNull()->default('whatsapp');
            $table->string('protocol', 50)->nullable();
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedBigInteger('finished_by')->nullable();
            $table->text('finish_notes')->nullable();
            $table->integer('unread_count')->notNull()->default(0);
            $table->boolean('is_bot_active')->notNull()->default(1);
            $table->json('bot_state')->nullable();
            $table->timestamps();
            $table->index('contact_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('flow_id');
            $table->index('last_message_at');
            $table->foreign('contact_id', 'contacts', 'id', 'CASCADE');
            $table->foreign('assigned_to', 'users', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        $this->dropTable('chats');
    }
}
