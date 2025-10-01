<?php

declare(strict_types=1);

namespace App\Domain\Communities\DTO;

final class ProfileActivityMigrationReport
{
    public function __construct(
        public readonly int $postsProcessed,
        public readonly int $commentsProcessed,
        public readonly int $completionsProcessed,
        public readonly int $recordsCreated,
        public readonly int $recordsUpdated,
        public readonly int $recordsSkipped,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'posts_processed' => $this->postsProcessed,
            'comments_processed' => $this->commentsProcessed,
            'completions_processed' => $this->completionsProcessed,
            'records_created' => $this->recordsCreated,
            'records_updated' => $this->recordsUpdated,
            'records_skipped' => $this->recordsSkipped,
        ];
    }
}
