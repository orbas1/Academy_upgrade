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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('lesson_type')->nullable();
            $table->string('duration')->nullable();
            $table->unsignedInteger('total_mark')->nullable();
            $table->unsignedInteger('pass_mark')->nullable();
            $table->unsignedInteger('retake')->nullable();
            $table->string('lesson_src')->nullable();
            $table->longText('attachment')->nullable();
            $table->string('attachment_type')->nullable();
            $table->text('video_type')->nullable();
            $table->string('thumbnail')->nullable();
            $table->integer('is_free')->nullable();
            $table->integer('sort')->nullable();
            $table->mediumText('description')->nullable();
            $table->longText('summary')->nullable();
            $table->integer('status')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('course_id');
            $table->index('section_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
