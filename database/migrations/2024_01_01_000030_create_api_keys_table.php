<?php

use Core\Migration;
use Core\Blueprint;

class CreateApiKeysTable extends Migration
{
    public function up(): void
    {
        $this->createTable('api_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->notNull();
            $table->string('name', 100)->notNull();
            $table->string('key', 80)->notNull();
            $table->timestamp('last_used_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->notNull()->default(1);
            $table->timestamps();
            $table->unique('key');
            $table->index('user_id');
            $table->foreign('user_id', 'users', 'id', 'CASCADE');
        });
        echo "  Created table: api_keys\n";
    }

    public function down(): void
    {
        $this->dropTable('api_keys');
    }
}
