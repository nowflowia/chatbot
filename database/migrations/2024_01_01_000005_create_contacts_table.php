<?php

use Core\Migration;
use Core\Blueprint;

class CreateContactsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 30)->notNull();
            $table->string('name', 200)->nullable();
            $table->string('email', 200)->nullable();
            $table->string('avatar', 500)->nullable();
            $table->string('whatsapp_id', 100)->nullable();
            $table->enum('status', ['active', 'blocked', 'inactive'])->notNull()->default('active');
            $table->text('notes')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique('phone');
            $table->index('whatsapp_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        $this->dropTable('contacts');
    }
}
