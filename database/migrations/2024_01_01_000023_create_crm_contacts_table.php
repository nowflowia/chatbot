<?php

use Core\Migration;
use Core\Blueprint;

class CreateCrmContactsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name', 200)->notNull();
            $table->string('role_title', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('linkedin', 200)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index('company_id');
            $table->index('name');
            $table->foreign('company_id', 'crm_companies', 'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        $this->dropTable('crm_contacts');
    }
}
