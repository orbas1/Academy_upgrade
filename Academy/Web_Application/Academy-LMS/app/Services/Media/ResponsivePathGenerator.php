<?php

namespace App\Services\Media;

class ResponsivePathGenerator
{
    public static function variantPath(string $originalPath, int $width, ?string $prefix = null): string
    {
        $prefix = trim($prefix ?? config('media.responsive_prefix', 'responsive'), '/');
        $directory = trim(dirname($originalPath), '/.');
        $filename = pathinfo($originalPath, PATHINFO_FILENAME) ?: 'image';
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'jpg';

        $segments = [];

        if ($prefix !== '') {
            $segments[] = $prefix;
        }

        if ($directory !== '') {
            $segments[] = $directory;
        }

        $basePath = implode('/', $segments);

        $variant = sprintf('%s-%d.%s', $filename, $width, $extension);

        return $basePath === '' ? $variant : $basePath . '/' . $variant;
    }
}
