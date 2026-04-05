<?php

use Core\Migration;
use Core\Blueprint;

class CreateCompanySettingsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 200)->notNull()->default('');
            $table->string('cnpj', 20)->notNull()->default('');
            $table->string('email', 150)->notNull()->default('');
            $table->string('phone', 30)->notNull()->default('');
            $table->string('address', 255)->notNull()->default('');
            $table->string('zip', 10)->notNull()->default('');
            $table->string('city', 100)->notNull()->default('');
            $table->string('state', 2)->notNull()->default('');
            $table->string('logo_path', 255)->notNull()->default('');
            $table->string('icon_path', 255)->notNull()->default('');
            $table->text('custom_css')->nullable();
            $table->string('primary_color', 7)->notNull()->default('#3b82f6');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTable('company_settings');
    }
}
