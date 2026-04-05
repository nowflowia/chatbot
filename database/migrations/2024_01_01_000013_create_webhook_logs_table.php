<?php

use Core\Migration;
use Core\Blueprint;

class CreateWebhookLogsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 100)->nullable();
            $table->string('phone_number_id', 100)->nullable();
            $table->string('from_number', 30)->nullable();
            $table->string('message_id', 200)->nullable();
            $table->enum('direction', ['inbound', 'outbound'])->notNull()->default('inbound');
            $table->enum('status', ['received', 'processed', 'failed', 'ignored'])->notNull()->default('received');
            $table->longText('payload')->nullable();
            $table->text('error')->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index('event_type');
            $table->index('from_number');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        $this->dropTable('webhook_logs');
    }
}
