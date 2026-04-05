<?php

use Core\Migration;
use Core\Blueprint;

class CreateWhatsappSettingsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->notNull()->default('Principal');
            $table->string('access_token', 1000)->nullable();
            $table->string('verify_token', 255)->nullable();
            $table->string('phone_number_id', 100)->nullable();
            $table->string('business_account_id', 100)->nullable();
            $table->string('app_id', 100)->nullable();
            $table->string('app_secret', 255)->nullable();
            $table->string('webhook_url', 500)->nullable();
            $table->string('api_version', 20)->notNull()->default('v25.0');
            $table->enum('status', ['active', 'inactive', 'error'])->notNull()->default('inactive');
            $table->text('last_error')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTable('whatsapp_settings');
    }
}
