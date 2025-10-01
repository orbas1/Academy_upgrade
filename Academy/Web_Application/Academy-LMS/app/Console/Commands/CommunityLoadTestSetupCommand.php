<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communities\DTO\CommunityLoadTestOptions;
use App\Domain\Communities\Services\CommunityLoadTestSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class CommunityLoadTestSetupCommand extends Command
{
    protected $signature = 'community:loadtest:prepare
        {--communities=1 : Number of communities to seed}
        {--members=75 : Active members to create per community}
        {--posts=6 : Posts to generate per member}
        {--comments=8 : Comments to fan out per post}
        {--reactions=20 : Reactions to record per post}
        {--points=4 : Points ledger events per member}
        {--tokens=15 : Member API tokens to mint per community}
        {--owner-password=Owner#Load123! : Owner password for generated accounts}
        {--member-password=Member#Load123! : Member password for generated accounts}
        {--skip-profile-activity : Disable seeding profile activity projections}
        {--output= : Optional path to persist the JSON payload}
    ';

    protected $description = 'Seed deterministic community data for load & resilience testing harnesses.';

    public function handle(CommunityLoadTestSeeder $seeder): int
    {
        $options = CommunityLoadTestOptions::fromArray([
            'community_count' => (int) $this->option('communities'),
            'members_per_community' => (int) $this->option('members'),
            'posts_per_member' => (int) $this->option('posts'),
            'comments_per_post' => (int) $this->option('comments'),
            'reactions_per_post' => (int) $this->option('reactions'),
            'points_events_per_member' => (int) $this->option('points'),
            'tokens_per_community' => (int) $this->option('tokens'),
            'owner_password' => (string) $this->option('owner-password'),
            'member_password' => (string) $this->option('member-password'),
            'seed_profile_activity' => ! (bool) $this->option('skip-profile-activity'),
        ]);

        $this->components->info('Seeding community load-test dataset');

        $summary = $seeder->seed($options);

        $this->components->twoColumnDetail('Communities', (string) $summary->communities);
        $this->components->twoColumnDetail('Members', (string) $summary->members);
        $this->components->twoColumnDetail('Posts', (string) $summary->posts);
        $this->components->twoColumnDetail('Comments', (string) $summary->comments);
        $this->components->twoColumnDetail('Reactions', (string) $summary->reactions);
        $this->components->twoColumnDetail('Points events', (string) $summary->pointsEvents);
        $this->components->twoColumnDetail('Profile activities', (string) $summary->profileActivities);

        $payload = json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        if ($output = $this->option('output')) {
            $directory = dirname((string) $output);
            if (! File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            File::put((string) $output, $payload);
            $this->components->twoColumnDetail('Credentials written', (string) $output);
        } else {
            $this->line('--- Credentials ---');
            $this->line($payload);
        }

        $this->newLine();
        $this->components->success('Community load-test dataset created.');

        return self::SUCCESS;
    }
}
