<?php

use Core\Migration;
use Core\Blueprint;

class CreateAiKnowledgeTables extends Migration
{
    public function up(): void
    {
        // Q&A pairs
        $this->createTable('ai_knowledge_qa', function (Blueprint $table) {
            $table->id();
            $table->string('question', 500)->notNull();
            $table->text('answer')->notNull();
            $table->boolean('is_active')->notNull()->default(1);
            $table->timestamps();
        });

        // Uploaded documents (PDF, MD, DOC, DOCX)
        $this->createTable('ai_knowledge_docs', function (Blueprint $table) {
            $table->id();
            $table->string('original_name', 255)->notNull();
            $table->string('stored_name', 255)->notNull();
            $table->string('mime', 120)->notNull();
            $table->integer('size_bytes')->notNull()->default(0);
            $table->boolean('is_active')->notNull()->default(1);
            $table->timestamps();
        });

        // Site URLs
        $this->createTable('ai_knowledge_sites', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500)->notNull();
            $table->string('title', 255)->nullable();
            $table->boolean('is_active')->notNull()->default(1);
            $table->dateTime('last_scraped_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTable('ai_knowledge_sites');
        $this->dropTable('ai_knowledge_docs');
        $this->dropTable('ai_knowledge_qa');
    }
}
