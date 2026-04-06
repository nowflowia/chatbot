<?php

use Core\Migration;
use Core\Blueprint;

class CreateCrmDealProductsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_deal_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_id')->notNull();
            $table->string('name', 200)->notNull();
            $table->text('description')->nullable();
            $table->integer('quantity')->notNull()->default(1);
            $table->decimal('unit_price', 10, 2)->notNull()->default(0.00);
            $table->timestamps();
            $table->index('deal_id');
            $table->foreign('deal_id', 'crm_deals', 'id', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('crm_deal_products');
    }
}
