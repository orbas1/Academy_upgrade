<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communities\Models\Community;
use App\Support\FeatureFlags\FeatureRolloutRepository;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class CommunityEnableFeatureCommand extends Command
{
    protected $signature = 'community:enable-feature
        {--flag=community_profile_activity : Feature flag to enable}
        {--percentage=100 : Percentage rollout}
        {--segment=internal : Segment label used for rollout metadata}
        {--community= : Comma separated list of community IDs or slugs to mark as beta launched}
        {--force : Skip confirmation prompt}';

    protected $description = 'Enable a community feature flag with rollout metadata and optional beta community updates.';

    public function handle(FeatureRolloutRepository $rollouts, Filesystem $files): int
    {
        $flag = (string) $this->option('flag');
        $percentage = (int) $this->option('percentage');
        $segments = collect(explode(',', (string) $this->option('segment')))
            ->map(fn ($segment) => trim((string) $segment))
            ->filter()
            ->values()
            ->all();

        if ($percentage < 0 || $percentage > 100) {
            $this->error('Percentage must be between 0 and 100.');

            return self::FAILURE;
        }

        if ($segments === []) {
            $segments = ['internal'];
        }

        if (! $this->option('force') && ! $this->confirm(sprintf('Enable feature "%s" at %d%% rollout?', $flag, $percentage))) {
            $this->comment('Command aborted.');

            return self::FAILURE;
        }

        $enabled = $percentage > 0;
        $this->writeBooleanFlag($files, $flag, $enabled);

        $rollouts->put($flag, [
            'enabled' => $enabled,
            'percentage' => $percentage,
            'segments' => $segments,
        ]);

        $communitiesOption = $this->option('community');
        if ($communitiesOption) {
            $identifiers = collect(explode(',', (string) $communitiesOption))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values();

            $communities = $this->resolveCommunities($identifiers->all());

            foreach ($communities as $community) {
                $this->markCommunityAsBeta($community, $segments);
            }

            $missing = $identifiers
                ->reject(fn ($id) => $communities->has($id))
                ->values();

            if ($missing->isNotEmpty()) {
                $this->warn('Some communities could not be resolved: ' . $missing->implode(', '));
            }
        }

        $this->info(sprintf('Feature "%s" enabled=%s at %d%% rollout.', $flag, $enabled ? 'true' : 'false', $percentage));

        return self::SUCCESS;
    }

    private function writeBooleanFlag(Filesystem $files, string $flag, bool $enabled): void
    {
        $path = storage_path('app/feature-flags.json');
        $directory = dirname($path);
        if (! $files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0755, true);
        }

        $flags = [];
        if ($files->exists($path)) {
            $decoded = json_decode($files->get($path), true);
            if (is_array($decoded)) {
                $flags = $decoded;
            }
        }

        $flags[$flag] = $enabled;

        $files->put($path, json_encode($flags, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }

    /**
     * @param  array<int, string>  $identifiers
     * @return \Illuminate\Support\Collection<string, Community>
     */
    private function resolveCommunities(array $identifiers)
    {
        $communities = collect();

        foreach ($identifiers as $identifier) {
            $community = is_numeric($identifier)
                ? Community::find((int) $identifier)
                : Community::where('slug', Str::slug($identifier))->first();

            if ($community) {
                $communities->put($identifier, $community);
            }
        }

        return $communities;
    }

    private function markCommunityAsBeta(Community $community, array $segments): void
    {
        $settings = $community->settings ?? [];
        if (! is_array($settings)) {
            $settings = [];
        }

        $rollout = $settings['rollout'] ?? [];
        $rollout['beta'] = [
            'enabled' => true,
            'segments' => $segments,
            'updated_at' => now()->toIso8601String(),
        ];
        $settings['rollout'] = $rollout;

        $community->settings = $settings;
        if (! $community->launched_at) {
            $community->launched_at = now();
        }
        $community->save();
    }
}
