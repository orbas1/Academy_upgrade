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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->nullable();
            $table->string('currency')->nullable();
            $table->string('title')->nullable();
            $table->string('model_name')->nullable();
            $table->text('description')->nullable();
            $table->text('keys')->nullable();
            $table->unsignedTinyInteger('status')->nullable();
            $table->unsignedTinyInteger('test_mode')->nullable();
            $table->unsignedTinyInteger('is_addon')->nullable();
            $table->timestamps();

            $table->index('identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
