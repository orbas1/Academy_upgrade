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
        Schema::create('watch_durations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('watched_student_id')->nullable();
            $table->unsignedInteger('watched_course_id')->nullable();
            $table->unsignedInteger('watched_lesson_id')->nullable();
            $table->unsignedInteger('current_duration')->nullable();
            $table->longText('watched_counter')->nullable();
            $table->timestamps();

            $table->index('watched_student_id');
            $table->index('watched_course_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_durations');
    }
};
