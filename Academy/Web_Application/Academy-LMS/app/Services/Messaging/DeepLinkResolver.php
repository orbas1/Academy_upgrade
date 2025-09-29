<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DeepLinkResolver
{
    /**
     * @param array<string, mixed> $context
     */
    public function forEvent(string $eventKey, array $context = []): ?string
    {
        $config = config('messaging.deep_links.events.'.$eventKey);

        if (! is_array($config)) {
            return null;
        }

        $path = $this->interpolate($config['path'] ?? '', $context);

        return $path ? $this->formatMobileUrl($path) : null;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function webUrlForEvent(string $eventKey, array $context = []): ?string
    {
        $config = config('messaging.deep_links.events.'.$eventKey);

        if (! is_array($config)) {
            return null;
        }

        $path = $this->interpolate($config['path'] ?? '', $context);

        return $path ? $this->formatWebUrl($path) : null;
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function interpolate(string $pattern, array $context): string
    {
        if ($pattern === '') {
            return '';
        }

        $replacements = [
            '{community}' => (string) Arr::get($context, 'community_id', Arr::get($context, 'data.community_id', '')),
            '{post}' => (string) Arr::get($context, 'data.post_id', ''),
            '{comment}' => (string) Arr::get($context, 'data.comment_id', ''),
        ];

        return Str::of($pattern)
            ->replace(array_keys($replacements), array_values($replacements))
            ->value();
    }

    protected function formatMobileUrl(string $path): string
    {
        $scheme = rtrim(config('messaging.deep_links.mobile_scheme', 'academy://'), '/');

        return $scheme.Str::start($path, '/');
    }

    protected function formatWebUrl(string $path): string
    {
        $base = rtrim(config('messaging.deep_links.web_base_url', config('app.url')), '/');

        return $base.Str::start($path, '/');
    }
}
