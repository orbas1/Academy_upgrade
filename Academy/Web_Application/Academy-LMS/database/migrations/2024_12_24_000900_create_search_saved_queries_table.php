<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_saved_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('index');
            $table->string('query')->nullable();
            $table->json('filters')->nullable();
            $table->json('flags')->nullable();
            $table->json('sort')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'index']);
            $table->index(['is_shared', 'index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_saved_queries');
    }
};

