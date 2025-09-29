<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('queue_name');
            $table->string('connection_name')->default('redis');
            $table->unsignedInteger('pending_jobs');
            $table->unsignedInteger('reserved_jobs');
            $table->unsignedInteger('delayed_jobs');
            $table->unsignedInteger('oldest_pending_seconds')->nullable();
            $table->unsignedInteger('oldest_reserved_seconds')->nullable();
            $table->unsignedInteger('oldest_delayed_seconds')->nullable();
            $table->decimal('backlog_delta_per_minute', 8, 2)->nullable();
            $table->timestamp('recorded_at')->useCurrent();

            $table->index(['queue_name', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_metrics');
    }
};
