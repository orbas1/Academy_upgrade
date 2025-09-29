<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Data;

class CommunityEmailMessage
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $event,
        public readonly string $subject,
        public readonly string $view,
        public readonly array $data,
        public readonly ?string $previewText = null,
        public readonly string $locale = 'en'
    ) {
    }
}
