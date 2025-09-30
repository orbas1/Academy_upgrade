<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_provider_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('channel');
            $table->string('provider');
            $table->boolean('healthy')->default(true);
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamp('last_recovered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'provider']);
        });

        Schema::create('notification_delivery_metrics', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('channel');
            $table->string('event')->nullable();
            $table->string('provider')->nullable();
            $table->string('status');
            $table->json('context')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index(['occurred_at']);
        });

        Schema::create('notification_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('channel');
            $table->string('identifier');
            $table->string('reason');
            $table->string('provider')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('suppressed_at');
            $table->timestamps();

            $table->unique(['channel', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_suppressions');
        Schema::dropIfExists('notification_delivery_metrics');
        Schema::dropIfExists('notification_provider_statuses');
    }
};
