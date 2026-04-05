<?php

use Core\Migration;
use Core\Blueprint;

class CreateRolesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->notNull();
            $table->string('slug', 100)->notNull();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->notNull()->default('active');
            $table->timestamps();
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        $this->dropTable('roles');
    }
}
