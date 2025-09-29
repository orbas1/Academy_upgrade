<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Data;

class CommunityPushMessage
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $event,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data,
        public readonly string $locale = 'en'
    ) {
    }
}
