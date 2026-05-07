<?php

use Core\Migration;
use Core\Blueprint;

class CreateAiPersonaTable extends Migration
{
    public function up(): void
    {
        $this->createTable('ai_persona', function (Blueprint $table) {
            $table->id();
            $table->text('prompt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTable('ai_persona');
    }
}
