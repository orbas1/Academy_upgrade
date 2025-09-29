<?php

namespace App\Domain\Search\Jobs;

use App\Services\Search\MeilisearchClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PushDocumentsToIndex implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int, array<string, mixed>> $documents
     */
    public function __construct(
        public string $index,
        public array $documents
    ) {
        $this->onQueue('search-index');
    }

    public function handle(MeilisearchClient $client): void
    {
        if (empty($this->documents)) {
            return;
        }

        try {
            $client->upsertDocuments($this->index, $this->documents);
        } catch (Throwable $exception) {
            $this->fail($exception);
        }
    }
}
