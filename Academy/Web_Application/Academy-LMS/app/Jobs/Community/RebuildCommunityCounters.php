<?php

declare(strict_types=1);

namespace App\Jobs\Community;

use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class RebuildCommunityCounters implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var array<string, mixed>
     */
    public array $payload;

    public string $queue = 'metrics';

    public int $tries = 2;

    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $communityId = (int) Arr::get($this->payload, 'community_id');

        if ($communityId <= 0) {
            Log::debug('community.metrics.rebuild.skipped', ['reason' => 'missing_community_id', 'payload' => $this->payload]);

            return;
        }

        $community = Community::query()->find($communityId);

        if (! $community) {
            Log::debug('community.metrics.rebuild.skipped', ['reason' => 'community_not_found', 'community_id' => $communityId]);

            return;
        }

        $activeMembers = CommunityMember::query()
            ->where('community_id', $communityId)
            ->where('status', 'active')
            ->count();

        $onlineMembers = CommunityMember::query()
            ->where('community_id', $communityId)
            ->where('is_online', true)
            ->count();

        $postCount = CommunityPost::query()
            ->where('community_id', $communityId)
            ->count();

        $commentCount = CommunityPostComment::query()
            ->where('community_id', $communityId)
            ->count();

        $settings = $community->settings ?? [];
        $settings['metrics'] = [
            'members' => $activeMembers,
            'online' => $onlineMembers,
            'posts' => $postCount,
            'comments' => $commentCount,
            'calculated_at' => now()->toIso8601String(),
        ];

        $community->forceFill(['settings' => $settings])->save();

        Log::info('community.metrics.rebuild.completed', [
            'community_id' => $communityId,
            'members' => $activeMembers,
            'online' => $onlineMembers,
            'posts' => $postCount,
            'comments' => $commentCount,
        ]);
    }
}
