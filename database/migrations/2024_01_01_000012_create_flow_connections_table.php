<?php

use Core\Migration;
use Core\Blueprint;

class CreateFlowConnectionsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('flow_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_id')->notNull();
            $table->unsignedBigInteger('source_node_id')->notNull();
            $table->unsignedBigInteger('target_node_id')->notNull();
            $table->string('source_port', 50)->notNull()->default('output');
            $table->string('target_port', 50)->notNull()->default('input');
            $table->string('label', 200)->nullable();
            $table->string('condition_value', 500)->nullable();
            $table->integer('sort_order')->notNull()->default(0);
            $table->timestamps();
            $table->index('flow_id');
            $table->index('source_node_id');
            $table->index('target_node_id');
            $table->foreign('flow_id', 'flows', 'id', 'CASCADE');
            $table->foreign('source_node_id', 'flow_nodes', 'id', 'CASCADE');
            $table->foreign('target_node_id', 'flow_nodes', 'id', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('flow_connections');
    }
}
