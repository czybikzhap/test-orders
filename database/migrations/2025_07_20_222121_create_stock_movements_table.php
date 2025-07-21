<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->integer('quantity_change');
            $table->string('movement_type');
            $table->string('description');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->timestamps();


            $table->index(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id']);
            $table->index(['product_id']);
            $table->index(['created_at']);
            $table->index(['movement_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
