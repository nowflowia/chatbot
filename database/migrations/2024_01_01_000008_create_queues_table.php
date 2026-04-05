<?php

use Core\Migration;
use Core\Blueprint;

class CreateQueuesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('queues', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->notNull();
            $table->string('slug', 150)->notNull();
            $table->text('description')->nullable();
            $table->string('color', 20)->nullable()->default('#3b82f6');
            $table->integer('max_agents')->notNull()->default(0);
            $table->integer('max_chats_per_agent')->notNull()->default(5);
            $table->boolean('auto_assign')->notNull()->default(1);
            $table->unsignedBigInteger('flow_id')->nullable();
            $table->enum('status', ['active', 'inactive'])->notNull()->default('active');
            $table->integer('sort_order')->notNull()->default(0);
            $table->timestamps();
            $table->unique('slug');
            $table->index('status');
        });
    }

    public function down(): void
    {
        $this->dropTable('queues');
    }
}
