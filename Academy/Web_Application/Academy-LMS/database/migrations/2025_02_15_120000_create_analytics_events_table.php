<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_name');
            $table->string('event_group')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_hash')->nullable()->index();
            $table->unsignedBigInteger('community_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampTz('recorded_at')->useCurrent();
            $table->timestampTz('delivered_at')->nullable();
            $table->string('delivery_status')->default('pending');
            $table->text('delivery_error')->nullable();
            $table->timestampsTz();

            $table->index(['event_name', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
