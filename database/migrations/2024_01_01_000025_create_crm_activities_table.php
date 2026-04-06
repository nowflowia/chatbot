<?php

use Core\Migration;
use Core\Blueprint;

class CreateCrmActivitiesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_id')->notNull();
            $table->unsignedBigInteger('user_id')->notNull();
            $table->enum('type', ['note', 'stage_change', 'status_change', 'email', 'call', 'meeting', 'file', 'task_done'])->notNull()->default('note');
            $table->string('title', 200)->notNull();
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index('deal_id');
            $table->index('user_id');
            $table->index('type');
            $table->foreign('deal_id', 'crm_deals', 'id', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('crm_activities');
    }
}
