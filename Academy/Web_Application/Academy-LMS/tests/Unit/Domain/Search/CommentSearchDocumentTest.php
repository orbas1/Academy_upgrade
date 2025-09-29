<?php

namespace Tests\Unit\Domain\Search;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentSearchDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_comment_document_with_post_context(): void
    {
        $author = User::factory()->create(['name' => 'Alice']);
        $community = Community::create([
            'slug' => 'community-lab',
            'name' => 'Community Lab',
            'visibility' => 'public',
            'join_policy' => 'open',
            'created_by' => $author->id,
            'updated_by' => $author->id,
        ]);

        $tier = CommunitySubscriptionTier::create([
            'community_id' => $community->id,
            'name' => 'Supporter',
            'slug' => 'supporter',
            'currency' => 'USD',
            'price_cents' => 1500,
            'billing_interval' => 'monthly',
            'is_public' => true,
        ]);

        $post = CommunityPost::create([
            'community_id' => $community->id,
            'author_id' => $author->id,
            'type' => 'text',
            'body_md' => 'Initial update',
            'body_html' => '<p>Initial update</p>',
            'visibility' => 'paid',
            'paywall_tier_id' => $tier->id,
            'metadata' => ['title' => 'Initial Update'],
            'published_at' => now()->subHour(),
        ]);

        $commentAuthor = User::factory()->create(['name' => 'Bob']);
        $comment = CommunityPostComment::create([
            'community_id' => $community->id,
            'post_id' => $post->id,
            'author_id' => $commentAuthor->id,
            'body_md' => 'Thanks for the details!',
            'body_html' => '<p>Thanks for the details!</p>',
            'mentions' => ['@alice'],
            'like_count' => 5,
            'reply_count' => 1,
        ]);

        $document = $comment->fresh()->toSearchRecord();

        $this->assertSame($comment->id, $document['id']);
        $this->assertSame($post->id, $document['post_id']);
        $this->assertSame($community->id, $document['community_id']);
        $this->assertSame('Community Lab', $community->name);
        $this->assertSame('Bob', $document['author']['name']);
        $this->assertEqualsCanonicalizing(['@alice'], $document['mentions']);
        $this->assertSame('paid', $document['visibility']);
        $this->assertSame($tier->id, $document['paywall_tier_id']);
        $this->assertSame('Initial Update', $document['post']['title']);
        $this->assertSame(1, $document['engagement']['reply_count']);
        $this->assertSame(5, $document['engagement']['like_count']);
    }
}
