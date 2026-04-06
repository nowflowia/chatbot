<?php

use Core\Migration;
use Core\Blueprint;

class CreateCrmStagesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pipeline_id')->notNull();
            $table->string('name', 100)->notNull();
            $table->string('color', 20)->notNull()->default('#6366f1');
            $table->integer('sort_order')->notNull()->default(0);
            $table->boolean('is_won')->notNull()->default(0);
            $table->boolean('is_lost')->notNull()->default(0);
            $table->timestamps();
            $table->index('pipeline_id');
            $table->index('sort_order');
            $table->foreign('pipeline_id', 'crm_pipelines', 'id', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('crm_stages');
    }
}
