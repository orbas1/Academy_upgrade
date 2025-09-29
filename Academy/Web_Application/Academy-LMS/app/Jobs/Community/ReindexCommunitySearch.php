<?php

declare(strict_types=1);

namespace App\Jobs\Community;

use App\Domain\Search\SearchSyncManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ReindexCommunitySearch implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var array<string, mixed>
     */
    public array $payload;

    public string $queue = 'search-index';

    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    public function handle(SearchSyncManager $manager): void
    {
        $modelClass = Arr::get($this->payload, 'model');
        $modelId = Arr::get($this->payload, 'id');

        if ($modelClass && $modelId) {
            $model = $modelClass::query()->find($modelId);

            if (! $model) {
                Log::debug('community.search.sync.skipped', [
                    'reason' => 'model_not_found',
                    'model' => $modelClass,
                    'id' => $modelId,
                ]);

                return;
            }

            $manager->queueModelSync($model);

            return;
        }

        $resource = Arr::get($this->payload, 'resource');

        try {
            $manager->dispatchFullSync($resource);
        } catch (\Throwable $exception) {
            Log::error('community.search.sync.failed', [
                'resource' => $resource,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }
}
