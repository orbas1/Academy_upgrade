<?php

namespace App\Services\Media;

class MediaUploadResult
{
    public function __construct(
        public readonly string $disk,
        public readonly string $path,
        public readonly string $url,
        public readonly array $variants = [],
        public readonly array $derivatives = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'disk' => $this->disk,
            'path' => $this->path,
            'url' => $this->url,
            'variants' => $this->variants,
            'derivatives' => $this->derivatives,
        ];
    }
}
