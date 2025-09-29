<?php

declare(strict_types=1);

namespace App\Domain\Search\Services;

use App\Domain\Search\Data\SearchVisibilityContext;
use Carbon\CarbonImmutable;
use JsonException;
use RuntimeException;

class SearchVisibilityTokenService
{
    public function __construct(
        private ?string $secret = null,
        private readonly string $algorithm = 'sha256'
    ) {
    }

    /**
     * @return array{token: string, filters: array<int, string>, issued_at: string, expires_at: string}
     */
    public function issue(SearchVisibilityContext $context): array
    {
        $payload = $context->toArray();
        $encoded = $this->encodePayload($payload);
        $signature = $this->sign($encoded);

        return [
            'token' => sprintf('%s.%s', $encoded, $signature),
            'filters' => $this->compileFilters($context),
            'issued_at' => $context->issuedAt->toIso8601String(),
            'expires_at' => $context->expiresAt->toIso8601String(),
        ];
    }

    public function validate(string $token): SearchVisibilityContext
    {
        [$encoded, $signature] = $this->splitToken($token);

        if (! hash_equals($this->sign($encoded), $signature)) {
            throw new RuntimeException('Search visibility token signature mismatch.');
        }

        $payload = $this->decodePayload($encoded);
        $context = SearchVisibilityContext::fromArray($payload);

        if ($context->expiresAt->lessThanOrEqualTo(CarbonImmutable::now())) {
            throw new RuntimeException('Search visibility token has expired.');
        }

        return $context;
    }

    /**
     * @return array<int, string>
     */
    public function compileFilters(SearchVisibilityContext $context): array
    {
        $filters = [];

        if ($context->includePublic) {
            $filters[] = "visibility = 'public'";
        }

        if ($context->includeCommunity && ! empty($context->communityIds)) {
            $filters[] = sprintf(
                "(visibility = 'community' AND community_id IN [%s])",
                implode(', ', $context->communityIds)
            );
        }

        if ($context->includePaid) {
            $paidClauses = [];

            if (! empty($context->unrestrictedPaidCommunityIds)) {
                $paidClauses[] = sprintf(
                    'community_id IN [%s]',
                    implode(', ', $context->unrestrictedPaidCommunityIds)
                );
            }

            if (! empty($context->subscriptionTierIds)) {
                $tierCommunityIds = ! empty($context->communityIds)
                    ? $context->communityIds
                    : $context->unrestrictedPaidCommunityIds;

                $subClauses = [
                    sprintf('paywall_tier_id IN [%s]', implode(', ', $context->subscriptionTierIds)),
                ];

                if (! empty($tierCommunityIds)) {
                    $subClauses[] = sprintf('community_id IN [%s]', implode(', ', $tierCommunityIds));
                }

                $paidClauses[] = '(' . implode(' AND ', $subClauses) . ')';
            }

            if (empty($paidClauses) && ! empty($context->communityIds)) {
                $paidClauses[] = sprintf('community_id IN [%s] AND paywall_tier_id IS NULL', implode(', ', $context->communityIds));
            }

            if (! empty($paidClauses)) {
                $filters[] = sprintf(
                    "(visibility = 'paid' AND (%s))",
                    implode(' OR ', $paidClauses)
                );
            }
        }

        return $filters;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function encodePayload(array $payload): string
    {
        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Failed to encode search visibility payload.', 0, $exception);
        }

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodePayload(string $encoded): array
    {
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Search visibility token payload could not be decoded.');
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Search visibility token payload is invalid JSON.', 0, $exception);
        }

        return $payload;
    }

    protected function splitToken(string $token): array
    {
        $segments = explode('.', $token, 2);

        if (count($segments) !== 2) {
            throw new RuntimeException('Search visibility token format is invalid.');
        }

        return $segments;
    }

    protected function sign(string $encoded): string
    {
        $secret = $this->resolveSecret();
        $signature = hash_hmac($this->algorithm, $encoded, $secret, false);

        if ($signature === false) {
            throw new RuntimeException('Unable to sign search visibility token.');
        }

        return $signature;
    }

    protected function resolveSecret(): string
    {
        if ($this->secret === null || $this->secret === '') {
            $this->secret = (string) config('search.visibility.token_secret');
        }

        if (empty($this->secret)) {
            throw new RuntimeException('Search visibility token secret is not configured.');
        }

        if (str_starts_with($this->secret, 'base64:')) {
            $decoded = base64_decode(substr($this->secret, 7), true);
            if ($decoded === false) {
                throw new RuntimeException('Invalid base64 search visibility token secret.');
            }

            $this->secret = $decoded;
        }

        return $this->secret;
    }
}

