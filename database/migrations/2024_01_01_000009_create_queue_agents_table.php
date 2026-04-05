<?php

use Core\Migration;
use Core\Blueprint;

class CreateQueueAgentsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('queue_agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('queue_id')->notNull();
            $table->unsignedBigInteger('user_id')->notNull();
            $table->enum('status', ['online', 'offline', 'busy', 'away'])->notNull()->default('offline');
            $table->integer('active_chats')->notNull()->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->index('queue_id');
            $table->index('user_id');
            $table->index('status');
            $table->foreign('queue_id', 'queues', 'id', 'CASCADE');
            $table->foreign('user_id', 'users', 'id', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('queue_agents');
    }
}
