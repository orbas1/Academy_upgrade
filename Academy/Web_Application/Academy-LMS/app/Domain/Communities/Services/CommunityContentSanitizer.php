<?php

namespace App\Domain\Communities\Services;

use Illuminate\Support\Str;

class CommunityContentSanitizer
{
    public function sanitizeMarkdown(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = Str::of($value)
            ->replaceMatches('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '')
            ->replaceMatches('/\x00|\x1F/', '')
            ->trim()
            ->toString();

        return $clean === '' ? null : $clean;
    }

    public function sanitizeHtml(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $allowedTags = config('communities.sanitizer.allowed_tags', '');

        $clean = Str::of(strip_tags($value, $allowedTags))
            ->replaceMatches('/on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\')/i', '')
            ->replaceMatches('/javascript:/i', '')
            ->replaceMatches('/data:\s*text\/html/i', '')
            ->replaceMatches('/style\s*=\s*("[^"]*"|\'[^\']*\')/i', '')
            ->trim()
            ->toString();

        return $clean === '' ? null : $clean;
    }
}
