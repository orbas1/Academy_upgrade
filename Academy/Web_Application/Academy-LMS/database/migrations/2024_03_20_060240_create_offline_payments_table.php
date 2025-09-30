<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('offline_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('item_type')->nullable();
            $table->string('items')->nullable();
            $table->decimal('tax', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('coupon')->nullable();
            $table->string('phone_no')->nullable();
            $table->string('bank_no')->nullable();
            $table->string('doc')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('offline_payments');
    }
};
