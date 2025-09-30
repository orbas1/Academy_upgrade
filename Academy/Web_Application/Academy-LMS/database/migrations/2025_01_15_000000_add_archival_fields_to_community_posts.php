<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('is_pinned');
            $table->timestamp('archived_at')->nullable()->after('is_archived');
            $table->json('lifecycle')->nullable()->after('metadata');
            $table->index(['community_id', 'is_archived', 'published_at'], 'community_posts_archived_visibility_idx');
        });
    }

    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropIndex('community_posts_archived_visibility_idx');
            $table->dropColumn(['is_archived', 'archived_at', 'lifecycle']);
        });
    }
};
