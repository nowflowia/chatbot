<?php

use Core\Migration;
use Core\Blueprint;

class CreateFlowNodesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('flow_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_id')->notNull();
            $table->enum('type', ['message', 'list', 'transfer', 'question', 'flow_ia', 'wait', 'finish', 'condition', 'api_call'])->notNull()->default('message');
            $table->string('title', 200)->notNull()->default('Novo Bloco');
            $table->longText('config_json')->nullable();
            $table->integer('pos_x')->notNull()->default(100);
            $table->integer('pos_y')->notNull()->default(100);
            $table->boolean('is_start')->notNull()->default(0);
            $table->enum('status', ['active', 'inactive'])->notNull()->default('active');
            $table->timestamps();
            $table->index('flow_id');
            $table->index('type');
            $table->index('is_start');
            $table->foreign('flow_id', 'flows', 'id', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('flow_nodes');
    }
}
