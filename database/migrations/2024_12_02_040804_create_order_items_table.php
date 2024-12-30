<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignUlid('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity')->unsigned();
            $table->decimal('price', 15, 2);
            $table->decimal('total', 15, 2)->storedAs('quantity * price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
