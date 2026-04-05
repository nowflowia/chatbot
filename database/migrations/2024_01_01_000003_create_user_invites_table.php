<?php

use Core\Migration;
use Core\Blueprint;

class CreateUserInvitesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('user_invites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->notNull();
            $table->string('token', 128)->notNull();
            $table->enum('type', ['set_password', 'reset_password'])->notNull()->default('set_password');
            $table->enum('status', ['pending', 'used', 'expired'])->notNull()->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->unique('token');
            $table->index('user_id');
            $table->index('status');
            $table->foreign('user_id', 'users', 'id', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('user_invites');
    }
}
