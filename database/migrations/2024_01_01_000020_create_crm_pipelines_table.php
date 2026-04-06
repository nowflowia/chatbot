<?php

use Core\Migration;
use Core\Blueprint;

class CreateCrmPipelinesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->notNull();
            $table->string('slug', 100)->notNull();
            $table->text('description')->nullable();
            $table->boolean('is_default')->notNull()->default(0);
            $table->boolean('is_active')->notNull()->default(1);
            $table->integer('sort_order')->notNull()->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique('slug');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        $this->dropTable('crm_pipelines');
    }
}
