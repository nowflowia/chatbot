<?php

use Core\Migration;
use Core\Blueprint;

class CreateFlowsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('flows', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->notNull();
            $table->string('slug', 150)->notNull();
            $table->text('description')->nullable();
            $table->enum('trigger', ['keyword', 'start', 'always', 'manual'])->notNull()->default('keyword');
            $table->text('trigger_keywords')->nullable();
            $table->boolean('is_active')->notNull()->default(0);
            $table->boolean('is_default')->notNull()->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->unique('slug');
            $table->index('is_active');
            $table->index('trigger');
        });
    }

    public function down(): void
    {
        $this->dropTable('flows');
    }
}
