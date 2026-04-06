<?php

use Core\Migration;
use Core\Blueprint;

class CreateCrmDealsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_deals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pipeline_id')->notNull();
            $table->unsignedBigInteger('stage_id')->notNull();
            $table->string('title', 200)->notNull();
            $table->decimal('value', 10, 2)->notNull()->default(0.00);
            $table->enum('status', ['open', 'won', 'lost'])->notNull()->default('open');
            $table->enum('origin', ['manual', 'whatsapp', 'import', 'api'])->notNull()->default('manual');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->date('expected_close_date')->nullable();
            $table->text('lost_reason')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->notNull()->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index('pipeline_id');
            $table->index('stage_id');
            $table->index('status');
            $table->index('assigned_to');
            $table->index('company_id');
            $table->foreign('pipeline_id', 'crm_pipelines', 'id', 'CASCADE');
            $table->foreign('stage_id',    'crm_stages',    'id', 'CASCADE');
            $table->foreign('company_id',  'crm_companies', 'id', 'SET NULL');
            $table->foreign('contact_id',  'crm_contacts',  'id', 'SET NULL');
        });
    }

    public function down(): void
    {
        $this->dropTable('crm_deals');
    }
}
