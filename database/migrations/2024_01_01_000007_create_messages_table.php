<?php

use Core\Migration;
use Core\Blueprint;

class CreateMessagesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id')->notNull();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('whatsapp_message_id', 200)->nullable();
            $table->enum('direction', ['inbound', 'outbound'])->notNull()->default('inbound');
            $table->enum('type', ['text', 'image', 'audio', 'video', 'document', 'location', 'sticker', 'reaction', 'template', 'interactive', 'system'])->notNull()->default('text');
            $table->longText('content')->nullable();
            $table->string('media_url', 1000)->nullable();
            $table->string('media_mime', 100)->nullable();
            $table->string('media_filename', 500)->nullable();
            $table->bigInteger('media_size')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->notNull()->default('pending');
            $table->text('error_message')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index('chat_id');
            $table->index('whatsapp_message_id');
            $table->index('direction');
            $table->index('status');
            $table->index('created_at');
            $table->foreign('chat_id', 'chats', 'id', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('messages');
    }
}
