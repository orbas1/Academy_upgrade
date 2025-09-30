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
        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->unsignedBigInteger('contact_one')->nullable();
            $table->unsignedBigInteger('contact_two')->nullable();
            $table->timestamps();

            $table->index('contact_one');
            $table->index('contact_two');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_threads');
    }
};
