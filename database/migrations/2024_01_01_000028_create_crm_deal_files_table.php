<?php

use Core\Migration;
use Core\Blueprint;

class CreateCrmDealFilesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_deal_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_id')->notNull();
            $table->string('filename', 200)->notNull();
            $table->string('original_name', 200)->notNull();
            $table->string('mime_type', 100)->nullable();
            $table->integer('size')->notNull()->default(0);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->index('deal_id');
            $table->foreign('deal_id', 'crm_deals', 'id', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('crm_deal_files');
    }
}
