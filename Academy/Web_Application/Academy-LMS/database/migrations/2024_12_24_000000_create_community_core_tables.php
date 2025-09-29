<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('icon_path')->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('geo_places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('bounding_box')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('timezone')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->text('bio')->nullable();
            $table->longText('about_html')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('avatar_path')->nullable();
            $table->json('links')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('community_categories')->nullOnDelete();
            $table->enum('visibility', ['public', 'private', 'unlisted'])->default('public');
            $table->enum('join_policy', ['open', 'request', 'invite'])->default('open');
            $table->enum('default_post_visibility', ['community', 'public', 'paid'])->default('community');
            $table->foreignId('geo_place_id')->nullable()->constrained('geo_places')->nullOnDelete();
            $table->string('timezone')->nullable();
            $table->string('locale', 12)->default('en');
            $table->unsignedInteger('max_members')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('launched_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['visibility', 'join_policy']);
            $table->index(['category_id', 'is_featured']);
        });

        Schema::create('community_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->nullable()->constrained('communities')->cascadeOnDelete();
            $table->unsignedInteger('level');
            $table->string('name');
            $table->string('badge_path')->nullable();
            $table->unsignedInteger('points_required');
            $table->text('description')->nullable();
            $table->json('rewards')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['community_id', 'level']);
        });

        Schema::create('community_points_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->nullable()->constrained('communities')->cascadeOnDelete();
            $table->string('action');
            $table->integer('points');
            $table->unsignedInteger('cooldown_seconds')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['community_id', 'action']);
        });

        Schema::create('community_admin_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->json('moderation_rules')->nullable();
            $table->json('membership_requirements')->nullable();
            $table->json('posting_policies')->nullable();
            $table->json('escalation_contacts')->nullable();
            $table->json('automation_settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('community_id');
        });

        Schema::create('community_subscription_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('currency', 3);
            $table->unsignedInteger('price_cents');
            $table->enum('billing_interval', ['monthly', 'quarterly', 'yearly']);
            $table->unsignedInteger('trial_days')->default(0);
            $table->boolean('is_public')->default(true);
            $table->json('benefits')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['community_id', 'slug']);
        });

        Schema::create('community_paywall_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subscription_tier_id')->nullable()->constrained('community_subscription_tiers')->nullOnDelete();
            $table->timestamp('access_starts_at');
            $table->timestamp('access_ends_at')->nullable();
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['community_id', 'user_id', 'subscription_tier_id', 'access_starts_at'], 'community_paywall_access_unique');
        });

        Schema::create('community_single_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('purchasable');
            $table->string('currency', 3);
            $table->unsignedInteger('amount_cents');
            $table->string('provider');
            $table->string('provider_reference')->nullable();
            $table->timestamp('purchased_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['community_id', 'purchased_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_single_purchases');
        Schema::dropIfExists('community_paywall_access');
        Schema::dropIfExists('community_subscription_tiers');
        Schema::dropIfExists('community_admin_settings');
        Schema::dropIfExists('community_points_rules');
        Schema::dropIfExists('community_levels');
        Schema::dropIfExists('communities');
        Schema::dropIfExists('geo_places');
        Schema::dropIfExists('community_categories');
    }
};
