<?php

use Core\Migration;
use Core\Blueprint;

class CreateAiSettingsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->notNull();
            $table->string('api_key', 500)->nullable();
            $table->string('model', 120)->notNull()->default('');
            $table->boolean('is_active')->notNull()->default(0);
            $table->dateTime('last_tested_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->timestamps();
            $table->unique('provider');
        });
    }

    public function down(): void
    {
        $this->dropTable('ai_settings');
    }
}
