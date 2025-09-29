<?php

namespace Tests\Unit\Domain\Search;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityCategory;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Domain\Communities\Models\GeoPlace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunitySearchDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_normalised_search_document(): void
    {
        $owner = User::factory()->create(['name' => 'Owner']);
        $category = CommunityCategory::create([
            'name' => 'Product',
            'is_active' => true,
        ]);
        $geo = GeoPlace::create([
            'name' => 'Lisbon',
            'type' => 'city',
            'country_code' => 'PT',
            'metadata' => ['city' => 'Lisbon', 'country' => 'Portugal'],
        ]);

        $community = Community::create([
            'slug' => 'makers-lounge',
            'name' => 'Makers Lounge',
            'tagline' => 'Build together.',
            'bio' => 'A space for makers to connect.',
            'category_id' => $category->id,
            'geo_place_id' => $geo->id,
            'visibility' => 'public',
            'join_policy' => 'open',
            'settings' => [
                'tags' => ['Design', 'Growth'],
                'location' => ['city' => 'Lisbon', 'country' => 'Portugal'],
            ],
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
            'launched_at' => now()->subDays(10),
            'is_featured' => true,
        ]);

        CommunityMember::create([
            'community_id' => $community->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now()->subDays(30),
            'last_seen_at' => now()->subHour(),
            'is_online' => true,
        ]);

        $secondMember = User::factory()->create();
        CommunityMember::create([
            'community_id' => $community->id,
            'user_id' => $secondMember->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now()->subDays(5),
            'last_seen_at' => now()->subDay(),
            'is_online' => false,
        ]);

        $removedMember = User::factory()->create();
        CommunityMember::create([
            'community_id' => $community->id,
            'user_id' => $removedMember->id,
            'role' => 'member',
            'status' => 'banned',
            'joined_at' => now()->subDays(2),
            'last_seen_at' => now()->subDays(2),
            'is_online' => true,
        ]);

        CommunitySubscriptionTier::create([
            'community_id' => $community->id,
            'name' => 'Pro',
            'slug' => 'pro',
            'currency' => 'USD',
            'price_cents' => 2900,
            'billing_interval' => 'monthly',
            'is_public' => true,
        ]);

        CommunityPost::create([
            'community_id' => $community->id,
            'author_id' => $owner->id,
            'type' => 'text',
            'body_md' => 'Launch update',
            'visibility' => 'community',
            'comment_count' => 2,
            'like_count' => 5,
            'published_at' => now()->subDay(),
        ]);

        $document = $community->fresh()->toSearchRecord();

        $this->assertSame($community->id, $document['id']);
        $this->assertEqualsCanonicalizing(['Design', 'Growth', 'Product'], $document['tags']);
        $this->assertSame('public', $document['visibility']);
        $this->assertSame(2, $document['member_count']);
        $this->assertSame(1, $document['online_count']);
        $this->assertSame('Makers Lounge', $document['name']);
        $this->assertSame('Lisbon', $document['location']['city']);
        $this->assertSame('Portugal', $document['location']['country']);
        $this->assertContains('Pro', $document['tier_names']);
        $this->assertNotEmpty($document['recent_activity_at']);
        $this->assertSame($category->name, $document['category']['name']);
    }
}
