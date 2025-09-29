<?php

namespace Tests\Unit\Domain\Search;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityCategory;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostSearchDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_search_document_with_engagement(): void
    {
        $author = User::factory()->create(['name' => 'Alice']);
        $community = Community::create([
            'slug' => 'product-lab',
            'name' => 'Product Lab',
            'visibility' => 'public',
            'join_policy' => 'open',
            'created_by' => $author->id,
            'updated_by' => $author->id,
        ]);
        $category = CommunityCategory::create(['name' => 'Growth']);
        $community->update(['category_id' => $category->id]);

        $tier = CommunitySubscriptionTier::create([
            'community_id' => $community->id,
            'name' => 'Insider',
            'slug' => 'insider',
            'currency' => 'USD',
            'price_cents' => 4900,
            'billing_interval' => 'monthly',
            'is_public' => true,
        ]);

        $post = CommunityPost::create([
            'community_id' => $community->id,
            'author_id' => $author->id,
            'type' => 'text',
            'body_md' => "**Launch** update for v1.0",
            'body_html' => '<p><strong>Launch</strong> update for v1.0</p>',
            'visibility' => 'paid',
            'paywall_tier_id' => $tier->id,
            'media' => [
                ['type' => 'image', 'url' => 'https://example.com/image.png'],
            ],
            'mentions' => ['@bob'],
            'topics' => ['announcements'],
            'metadata' => ['title' => 'Launch Update'],
            'like_count' => 4,
            'comment_count' => 3,
            'share_count' => 1,
            'view_count' => 100,
            'created_at' => now()->subDay(),
            'published_at' => now()->subMinutes(5),
        ]);

        $document = $post->fresh()->toSearchRecord();

        $this->assertSame($post->id, $document['id']);
        $this->assertSame($community->id, $document['community_id']);
        $this->assertSame('Launch Update', $document['title']);
        $this->assertTrue($document['is_paid']);
        $this->assertEqualsCanonicalizing(['announcements'], $document['topics']);
        $this->assertEqualsCanonicalizing(['@bob'], $document['mentions']);
        $this->assertEqualsCanonicalizing(['image'], $document['media']);
        $this->assertSame('Alice', $document['author']['name']);
        $this->assertSame($tier->id, $document['paywall_tier_id']);
        $this->assertEqualsWithDelta(23.25, $document['engagement']['score'], 0.001);
        $this->assertSame(3, $document['engagement']['comment_count']);
        $this->assertSame(4, $document['engagement']['reaction_count']);
        $this->assertNotEmpty($document['published_at']);
    }
}
