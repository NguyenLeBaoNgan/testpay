<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('gateway');
            $table->timestamp('transaction_date')->default(value: DB::raw('CURRENT_TIMESTAMP'));
            $table->string('account_number')->nullable();
            $table->string('sub_account', 250)->nullable();
            $table->decimal('amount_in', 20, 2)->default(0.00);
            $table->decimal('amount_out', 20, 2)->default(0.00);
            $table->decimal('accumulated', 20, 2)->default(0.00);
            $table->string('code', 250)->nullable();
            $table->text('transaction_content')->nullable();
            $table->string('reference_number', 255)->nullable();
            $table->text('body')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
