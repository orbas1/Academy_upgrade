<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->index(['community_id', 'published_at', 'id'], 'community_posts_feed_idx');
            $table->index(['community_id', 'type', 'published_at'], 'community_posts_type_visibility_idx');
            $table->index(['community_id', 'paywall_tier_id', 'visibility'], 'community_posts_paywall_idx');
            $table->index(['community_id', 'scheduled_at', 'id'], 'community_posts_schedule_idx');
        });

        Schema::table('community_post_comments', function (Blueprint $table) {
            $table->index(['community_id', 'post_id', 'created_at'], 'community_post_comments_feed_idx');
            $table->index(['post_id', 'parent_id', 'created_at'], 'community_post_comments_thread_idx');
        });

        Schema::table('community_points_ledger', function (Blueprint $table) {
            $table->index(['community_id', 'member_id', 'occurred_at'], 'community_points_ledger_member_idx');
        });

        Schema::table('community_members', function (Blueprint $table) {
            $table->index(['community_id', 'last_seen_at'], 'community_members_presence_idx');
            $table->index(['community_id', 'points'], 'community_members_points_idx');
        });

        Schema::table('community_follows', function (Blueprint $table) {
            $table->index(['follower_id', 'followable_type', 'followable_id'], 'community_follows_follower_idx');
        });

        Schema::table('community_paywall_access', function (Blueprint $table) {
            $table->index(['community_id', 'access_ends_at'], 'community_paywall_access_expiry_idx');
        });

        Schema::table('community_subscriptions', function (Blueprint $table) {
            $table->index(['community_id', 'status', 'renews_at'], 'community_subscriptions_status_idx');
        });

        $this->applyPartitions();
    }

    public function down(): void
    {
        $this->removePartitions();

        Schema::table('community_subscriptions', function (Blueprint $table) {
            $table->dropIndex('community_subscriptions_status_idx');
        });

        Schema::table('community_paywall_access', function (Blueprint $table) {
            $table->dropIndex('community_paywall_access_expiry_idx');
        });

        Schema::table('community_follows', function (Blueprint $table) {
            $table->dropIndex('community_follows_follower_idx');
        });

        Schema::table('community_members', function (Blueprint $table) {
            $table->dropIndex('community_members_presence_idx');
            $table->dropIndex('community_members_points_idx');
        });

        Schema::table('community_points_ledger', function (Blueprint $table) {
            $table->dropIndex('community_points_ledger_member_idx');
        });

        Schema::table('community_post_comments', function (Blueprint $table) {
            $table->dropIndex('community_post_comments_feed_idx');
            $table->dropIndex('community_post_comments_thread_idx');
        });

        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropIndex('community_posts_feed_idx');
            $table->dropIndex('community_posts_type_visibility_idx');
            $table->dropIndex('community_posts_paywall_idx');
            $table->dropIndex('community_posts_schedule_idx');
        });
    }

    private function applyPartitions(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $partitionedTables = [
            'community_posts' => 16,
            'community_post_comments' => 16,
            'community_points_ledger' => 8,
        ];

        foreach ($partitionedTables as $table => $partitions) {
            $isPartitioned = DB::table('information_schema.PARTITIONS')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', $table)
                ->whereNotNull('PARTITION_NAME')
                ->exists();

            if ($isPartitioned) {
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE `%s` PARTITION BY HASH(`id`) PARTITIONS %d',
                $table,
                $partitions
            ));
        }
    }

    private function removePartitions(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['community_points_ledger', 'community_post_comments', 'community_posts'] as $table) {
            $isPartitioned = DB::table('information_schema.PARTITIONS')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', $table)
                ->whereNotNull('PARTITION_NAME')
                ->exists();

            if (! $isPartitioned) {
                continue;
            }

            DB::statement(sprintf('ALTER TABLE `%s` REMOVE PARTITIONING', $table));
        }
    }
};
