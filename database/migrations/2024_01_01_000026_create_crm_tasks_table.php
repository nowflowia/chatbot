<?php

use Core\Migration;
use Core\Blueprint;

class CreateCrmTasksTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('title', 200)->notNull();
            $table->text('description')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->enum('status', ['open', 'done', 'cancelled'])->notNull()->default('open');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index('deal_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->foreign('deal_id',    'crm_deals',    'id', 'CASCADE');
            $table->foreign('contact_id', 'crm_contacts', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        $this->dropTable('crm_tasks');
    }
}
