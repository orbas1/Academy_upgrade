<?php

namespace App\Domain\Communities\Observers;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityAdminSetting;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPointsLedger;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use Illuminate\Support\Facades\Log;
use LogicException;

class CommunityObserver
{
    public function deleting(Community $community): void
    {
        if ($community->isForceDeleting()) {
            $hasRelations = $community->members()->withTrashed()->exists()
                || $community->posts()->withTrashed()->exists();

            if ($hasRelations) {
                throw new LogicException('Communities with memberships or posts cannot be permanently removed.');
            }

            $community->subscriptionTiers()->withTrashed()->get()->each->forceDelete();
            $community->leaderboards()->delete();
            $community->pointsRules()->delete();
            $community->adminSettings()->withTrashed()->first()?->forceDelete();

            return;
        }

        $community->members()->chunkById(config('communities.maintenance.chunk', 100), function ($members) {
            $members->each->delete();
        });

        $community->posts()->chunkById(config('communities.maintenance.chunk', 100), function ($posts) {
            $posts->each->delete();
        });

        $community->subscriptionTiers()->withTrashed()->get()->each(function (CommunitySubscriptionTier $tier) {
            if (! $tier->trashed()) {
                $tier->delete();
            }
        });

        $community->leaderboards()->delete();
        $community->pointsRules()->delete();

        $settings = $community->adminSettings()->withTrashed()->first();

        if ($settings instanceof CommunityAdminSetting && ! $settings->trashed()) {
            $settings->delete();
        }
    }

    public function restored(Community $community): void
    {
        CommunityMember::withTrashed()
            ->where('community_id', $community->id)
            ->chunkById(config('communities.maintenance.chunk', 100), function ($members) {
                $members->each->restore();
            });

        CommunityPost::withTrashed()
            ->where('community_id', $community->id)
            ->chunkById(config('communities.maintenance.chunk', 100), function ($posts) {
                $posts->each->restore();
            });

        CommunitySubscriptionTier::withTrashed()
            ->where('community_id', $community->id)
            ->restore();

        $community->adminSettings()->withTrashed()->first()?->restore();
    }

    public function forceDeleting(Community $community): void
    {
        if ($community->members()->withTrashed()->exists() || $community->posts()->withTrashed()->exists()) {
            Log::warning('Attempted to force delete community with related records', [
                'community_id' => $community->id,
            ]);

            throw new LogicException('Force delete denied for communities that still have dependent records.');
        }

        CommunityPointsLedger::where('community_id', $community->id)->delete();
    }
}
