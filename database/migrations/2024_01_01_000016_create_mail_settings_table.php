<?php

use Core\Migration;
use Core\Blueprint;

class CreateMailSettingsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->string('host', 255)->notNull()->default('smtp.gmail.com');
            $table->integer('port')->notNull()->default(587);
            $table->enum('encryption', ['tls', 'ssl', 'none'])->notNull()->default('tls');
            $table->string('username', 255)->nullable();
            $table->string('password', 500)->nullable();
            $table->string('from_address', 255)->notNull()->default('noreply@example.com');
            $table->string('from_name', 150)->notNull()->default('ChatBot');
            $table->boolean('is_active')->notNull()->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTable('mail_settings');
    }
}
