<?php

use Core\Migration;
use Core\Blueprint;

class CreateCrmCompaniesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->notNull();
            $table->string('fantasy_name', 200)->nullable();
            $table->string('cnpj', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('website', 200)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 50)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index('name');
        });
    }

    public function down(): void
    {
        $this->dropTable('crm_companies');
    }
}
