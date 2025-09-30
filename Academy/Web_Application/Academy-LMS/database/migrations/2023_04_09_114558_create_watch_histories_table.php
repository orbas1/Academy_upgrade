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
        Schema::create('watch_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('course_id')->nullable();
            $table->unsignedInteger('student_id')->nullable();
            $table->longText('completed_lesson')->nullable();
            $table->string('watching_lesson_id', 11)->nullable();
            $table->unsignedInteger('course_progress')->nullable();
            $table->string('completed_date', 11)->nullable();
            $table->timestamps();

            $table->index('course_id');
            $table->index('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_histories');
    }
};
