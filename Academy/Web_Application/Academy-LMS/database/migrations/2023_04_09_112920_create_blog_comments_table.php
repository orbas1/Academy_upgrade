<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blog_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedTinyInteger('check')->default(0);
            $table->longText('comment')->nullable();
            $table->unsignedInteger('likes')->default(0);
            $table->timestamps();

            $table->index('blog_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_comments');
    }
};
