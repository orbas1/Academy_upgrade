<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'moderator', 'member'])->default('member');
            $table->enum('status', ['active', 'pending', 'banned', 'left'])->default('active');
            $table->timestamp('joined_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_online')->default(false);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('lifetime_points')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->json('badges')->nullable();
            $table->json('preferences')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['community_id', 'user_id']);
            $table->index(['community_id', 'role']);
            $table->index(['community_id', 'status']);
        });

        Schema::create('community_points_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('community_members')->cascadeOnDelete();
            $table->string('action');
            $table->integer('points_delta');
            $table->unsignedInteger('balance_after');
            $table->morphs('source');
            $table->foreignId('acted_by')->nullable()->constrained('users');
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['community_id', 'occurred_at']);
        });

        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users');
            $table->enum('type', ['text', 'image', 'video', 'link', 'poll']);
            $table->mediumText('body_md')->nullable();
            $table->mediumText('body_html')->nullable();
            $table->json('media')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->enum('visibility', ['community', 'public', 'paid'])->default('community');
            $table->foreignId('paywall_tier_id')->nullable()->constrained('community_subscription_tiers')->nullOnDelete();
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('mentions')->nullable();
            $table->json('topics')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['community_id', 'is_pinned', 'created_at']);
            $table->index(['community_id', 'visibility', 'published_at']);
        });

        Schema::create('community_post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('parent_id')->nullable()->constrained('community_post_comments')->cascadeOnDelete();
            $table->mediumText('body_md');
            $table->mediumText('body_html')->nullable();
            $table->json('mentions')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('reply_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['post_id', 'created_at']);
        });

        Schema::create('community_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reaction', ['like', 'love', 'insightful', 'celebrate'])->default('like');
            $table->timestamp('reacted_at');
            $table->timestamps();
            $table->unique(['post_id', 'user_id']);
            $table->index(['reaction', 'post_id']);
        });

        Schema::create('community_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('community_post_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reaction', ['like', 'love', 'insightful', 'celebrate'])->default('like');
            $table->timestamp('reacted_at');
            $table->timestamps();
            $table->unique(['comment_id', 'user_id']);
        });

        Schema::create('community_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->nullable()->constrained('communities')->cascadeOnDelete();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('followable');
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamp('followed_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['follower_id', 'followable_type', 'followable_id'], 'community_follow_unique');
            $table->index(['community_id', 'followable_type']);
        });

        Schema::create('community_leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->enum('period', ['daily', 'weekly', 'monthly', 'all_time']);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('entries');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['community_id', 'period', 'starts_on', 'ends_on'], 'community_leaderboards_period_unique');
        });

        Schema::create('community_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscription_tier_id')->nullable()->constrained('community_subscription_tiers')->nullOnDelete();
            $table->string('provider')->default('stripe');
            $table->string('provider_subscription_id')->nullable();
            $table->enum('status', ['active', 'trialing', 'past_due', 'canceled', 'expired'])->default('active');
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['community_id', 'user_id', 'subscription_tier_id'], 'community_subscriptions_unique');
            $table->index(['status', 'renews_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_subscriptions');
        Schema::dropIfExists('community_leaderboards');
        Schema::dropIfExists('community_follows');
        Schema::dropIfExists('community_comment_likes');
        Schema::dropIfExists('community_post_likes');
        Schema::dropIfExists('community_post_comments');
        Schema::dropIfExists('community_posts');
        Schema::dropIfExists('community_points_ledger');
        Schema::dropIfExists('community_members');
    }
};
