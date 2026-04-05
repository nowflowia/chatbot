<?php

use Core\Migration;
use Core\Blueprint;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->createTable('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->notNull()->default(2);
            $table->string('name', 150)->notNull();
            $table->string('email', 200)->notNull();
            $table->string('password', 255)->nullable();
            $table->string('avatar', 500)->nullable();
            $table->string('phone', 30)->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->notNull()->default('pending');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 50)->nullable();
            $table->timestamps();
            $table->unique('email');
            $table->index('role_id');
            $table->index('status');
            $table->foreign('role_id', 'roles', 'id', 'RESTRICT');
        });
    }

    public function down(): void
    {
        $this->dropTable('users');
    }
}
