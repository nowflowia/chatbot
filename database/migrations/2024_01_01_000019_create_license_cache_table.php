<?php

use Core\Migration;
use Core\Blueprint;

class CreateLicenseCacheTable extends Migration
{
    public function up(): void
    {
        $this->createTable('license_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 60)->notNull();
            $table->text('payload')->notNull();
            $table->integer('checked_at')->notNull()->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTable('license_cache');
    }
}
