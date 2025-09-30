<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->longText('correct_answer')->nullable();
            $table->longText('wrong_answer')->nullable();
            $table->longText('submits')->nullable();
            $table->timestamps();

            $table->index('quiz_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_submissions');
    }
};
