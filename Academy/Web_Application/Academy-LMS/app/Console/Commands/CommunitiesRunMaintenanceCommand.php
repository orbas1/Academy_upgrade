<?php

namespace App\Console\Commands;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Services\CommunityDataIntegrityService;
use Illuminate\Console\Command;

class CommunitiesRunMaintenanceCommand extends Command
{
    protected $signature = 'communities:maintain {--community=} {--prune : Remove orphaned records}';

    protected $description = 'Run community data integrity reconciliation and optional orphan pruning.';

    public function __construct(private readonly CommunityDataIntegrityService $integrityService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $communityId = $this->option('community');
        $totals = [
            'posts_reconciled' => 0,
            'members_reconciled' => 0,
        ];

        $query = Community::query()->withTrashed();

        if ($communityId !== null) {
            $query->whereKey($communityId);
        }

        $query->chunkById(config('communities.maintenance.chunk', 200), function ($communities) use (&$totals) {
            foreach ($communities as $community) {
                $result = $this->integrityService->runMaintenance($community);
                $totals['posts_reconciled'] += $result['posts_reconciled'];
                $totals['members_reconciled'] += $result['members_reconciled'];
            }
        });

        $this->info(sprintf(
            'Reconciled %d posts and %d member records.',
            $totals['posts_reconciled'],
            $totals['members_reconciled']
        ));

        if ($this->option('prune')) {
            $pruned = $this->integrityService->pruneOrphans();
            $this->warn(sprintf(
                'Pruned orphans → posts: %d, comments: %d, post likes: %d, comment likes: %d',
                $pruned['posts_pruned'],
                $pruned['comments_pruned'],
                $pruned['post_likes_pruned'],
                $pruned['comment_likes_pruned']
            ));
        }

        $this->integrityService->analyzeTables();

        return self::SUCCESS;
    }
}
