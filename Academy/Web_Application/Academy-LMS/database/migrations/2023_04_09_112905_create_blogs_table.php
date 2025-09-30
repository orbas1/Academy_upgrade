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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('category_slug')->nullable();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->longText('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('banner')->nullable();
            $table->text('keywords')->nullable();
            $table->unsignedTinyInteger('is_popular')->nullable();
            $table->unsignedTinyInteger('status')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
