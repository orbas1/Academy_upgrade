<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('scope');
            $table->string('query')->nullable();
            $table->json('filters')->nullable();
            $table->string('sort')->nullable();
            $table->string('frequency')->default('none');
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index(['scope', 'frequency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_saved_searches');
    }
};

