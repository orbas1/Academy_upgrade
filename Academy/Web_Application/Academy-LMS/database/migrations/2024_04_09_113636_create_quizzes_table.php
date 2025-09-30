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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('title')->nullable();
            $table->string('duration')->nullable();
            $table->unsignedInteger('total_mark')->nullable();
            $table->unsignedInteger('pass_mark')->nullable();
            $table->unsignedInteger('drip_rule')->nullable();
            $table->longText('summary')->nullable();
            $table->longText('attempts')->nullable();
            $table->unsignedInteger('sort')->nullable();
            $table->timestamps();

            $table->index('course_id');
            $table->index('section_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
