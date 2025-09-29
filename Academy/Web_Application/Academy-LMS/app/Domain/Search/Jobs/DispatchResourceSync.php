<?php

namespace App\Domain\Search\Jobs;

use App\Domain\Search\SearchSyncManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class DispatchResourceSync implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public string $resource)
    {
        $this->onQueue('search-index');
    }

    public function handle(SearchSyncManager $manager): void
    {
        try {
            $manager->runResourceSync($this->resource, queueChunks: true);
        } catch (Throwable $exception) {
            $this->fail($exception);
        }
    }
}
