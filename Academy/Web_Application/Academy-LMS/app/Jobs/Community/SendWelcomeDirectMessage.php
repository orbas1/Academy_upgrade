<?php

declare(strict_types=1);

namespace App\Jobs\Community;

use App\Models\Community\CommunityMember;
use App\Jobs\Community\DistributeNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWelcomeDirectMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $memberId)
    {
        $this->onQueue('community');
    }

    public function handle(): void
    {
        /** @var CommunityMember|null $member */
        $member = CommunityMember::query()
            ->with('community.owner')
            ->find($this->memberId);

        if (! $member || ! $member->community) {
            return;
        }

        $owner = $member->community->owner;
        if (! $owner) {
            Log::warning('welcome_dm.no_owner', ['community_id' => $member->community_id]);

            return;
        }

        $template = Config::get('communities.automation.welcome_template', 'Welcome to %community_name%!');
        $message = str_replace('%community_name%', $member->community->name, $template);

        try {
            DistributeNotification::dispatch([
                'community_id' => $member->community_id,
                'event' => 'member.welcome_dm',
                'recipient_ids' => [$member->user_id],
                'data' => [
                    'subject' => sprintf('A welcome from %s leadership', $member->community->name),
                    'message' => $message,
                    'member_id' => $member->getKey(),
                    'sender_id' => $owner->user_id,
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('welcome_dm.failed', [
                'member_id' => $member->getKey(),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
