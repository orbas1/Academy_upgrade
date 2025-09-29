<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('search_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scope');
            $table->string('query')->nullable();
            $table->json('filters')->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->boolean('is_admin')->default(false);
            $table->timestamp('executed_at');
            $table->timestamps();

            $table->index(['scope', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_audit_logs');
    }
};

