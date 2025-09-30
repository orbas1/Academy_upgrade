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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id')->nullable();
            $table->longText('title')->nullable();
            $table->string('type')->nullable();
            $table->mediumText('answer')->nullable();
            $table->longText('options')->nullable();
            $table->unsignedInteger('sort')->nullable();
            $table->timestamps();

            $table->index('quiz_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
