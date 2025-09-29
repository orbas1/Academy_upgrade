<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_id')->nullable()->constrained('communities')->cascadeOnDelete();
            $table->boolean('channel_email')->default(true);
            $table->boolean('channel_push')->default(true);
            $table->boolean('channel_in_app')->default(true);
            $table->string('digest_frequency', 32)->default('daily');
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->json('muted_events')->nullable();
            $table->json('metadata')->nullable();
            $table->string('locale', 12)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'community_id']);
            $table->index(['community_id', 'digest_frequency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_notification_preferences');
    }
};
