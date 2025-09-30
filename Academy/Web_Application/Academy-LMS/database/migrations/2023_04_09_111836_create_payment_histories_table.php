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
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_type', 50)->nullable();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('invoice', 255)->nullable();
            $table->unsignedInteger('date_added')->nullable();
            $table->unsignedInteger('last_modified')->nullable();
            $table->decimal('admin_revenue', 12, 2)->nullable();
            $table->decimal('instructor_revenue', 12, 2)->nullable();
            $table->decimal('tax', 12, 2)->nullable();
            $table->unsignedTinyInteger('instructor_payment_status')->default(0);
            $table->string('transaction_id', 255)->nullable();
            $table->string('session_id', 255)->nullable();
            $table->string('coupon', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};
