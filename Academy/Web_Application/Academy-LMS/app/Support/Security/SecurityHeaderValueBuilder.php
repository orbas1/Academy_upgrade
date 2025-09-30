<?php

namespace App\Support\Security;

class SecurityHeaderValueBuilder
{
    /**
     * Build a Strict-Transport-Security header value.
     */
    public static function strictTransportSecurity(int $maxAge, bool $includeSubdomains = true, bool $preload = true): string
    {
        $segments = ["max-age={$maxAge}"];

        if ($includeSubdomains) {
            $segments[] = 'includeSubDomains';
        }

        if ($preload) {
            $segments[] = 'preload';
        }

        return implode('; ', $segments);
    }

    /**
     * Build a Content-Security-Policy header string from the provided directives.
     *
     * @param  array<string, array<int, string>|string|null>  $directives
     */
    public static function contentSecurityPolicy(array $directives): string
    {
        $parts = [];

        foreach ($directives as $directive => $sources) {
            $normalized = self::normalizeSources($sources);

            if (empty($normalized)) {
                continue;
            }

            $parts[] = sprintf('%s %s', $directive, implode(' ', $normalized));
        }

        return implode('; ', $parts);
    }

    /**
     * Build a Permissions-Policy header string from the provided feature map.
     *
     * @param  array<string, array<int, string>|string|null>  $policies
     */
    public static function permissionsPolicy(array $policies): string
    {
        $parts = [];

        foreach ($policies as $feature => $allowList) {
            $values = self::normalizePermissions($allowList);
            $parts[] = sprintf('%s=(%s)', $feature, implode(' ', $values));
        }

        return implode(', ', $parts);
    }

    /**
     * Merge a default set of sources with additional overrides provided via configuration.
     *
     * @param  array<int, string>  $defaults
     * @param  array<int, string>|string|null  $overrides
     * @return array<int, string>
     */
    public static function mergeSources(array $defaults, array|string|null $overrides): array
    {
        $merged = array_merge($defaults, self::normalizeSources($overrides));

        return array_values(array_unique($merged));
    }

    /**
     * Normalize a directive or policy source definition to an array of tokens.
     *
     * @param  array<int, string>|string|null  $value
     * @return array<int, string>
     */
    protected static function normalizeSources(array|string|null $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_string($value)) {
            $tokens = preg_split('/[\s,]+/', trim($value));
            $value = $tokens === false ? [] : $tokens;
        }

        $normalized = [];

        foreach ($value as $token) {
            $token = trim((string) $token);

            if ($token === '') {
                continue;
            }

            $normalized[] = $token;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Normalize the allow list for a permissions policy feature.
     *
     * @param  array<int, string>|string|null  $value
     * @return array<int, string>
     */
    protected static function normalizePermissions(array|string|null $value): array
    {
        $normalized = self::normalizeSources($value);

        if (empty($normalized)) {
            return [''];
        }

        return $normalized;
    }
}
