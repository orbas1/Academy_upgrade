<?php

namespace App\Domain\Search\Jobs;

use App\Services\Search\MeilisearchClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RemoveDocumentsFromIndex implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int, int|string> $identifiers
     */
    public function __construct(
        public string $index,
        public array $identifiers
    ) {
        $this->onQueue('search-index');
    }

    public function handle(MeilisearchClient $client): void
    {
        if (empty($this->identifiers)) {
            return;
        }

        try {
            $client->deleteDocuments($this->index, $this->identifiers);
        } catch (Throwable $exception) {
            $this->fail($exception);
        }
    }
}
