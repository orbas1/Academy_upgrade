<?php

declare(strict_types=1);

namespace App\Domain\Search\Data;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;

final class SearchVisibilityContext implements Arrayable
{
    public const CURRENT_VERSION = 1;

    /**
     * @param array<int, int> $communityIds
     * @param array<int, int> $unrestrictedPaidCommunityIds
     * @param array<int, int> $subscriptionTierIds
     */
    public function __construct(
        public readonly ?int $userId,
        public readonly array $communityIds,
        public readonly array $unrestrictedPaidCommunityIds,
        public readonly array $subscriptionTierIds,
        public readonly bool $includePublic,
        public readonly bool $includeCommunity,
        public readonly bool $includePaid,
        public readonly CarbonImmutable $issuedAt,
        public readonly CarbonImmutable $expiresAt,
        public readonly int $version = self::CURRENT_VERSION,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'user_id' => $this->userId,
            'community_ids' => $this->communityIds,
            'unrestricted_paid_community_ids' => $this->unrestrictedPaidCommunityIds,
            'subscription_tier_ids' => $this->subscriptionTierIds,
            'include_public' => $this->includePublic,
            'include_community' => $this->includeCommunity,
            'include_paid' => $this->includePaid,
            'issued_at' => $this->issuedAt->toIso8601String(),
            'expires_at' => $this->expiresAt->toIso8601String(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $issuedAt = CarbonImmutable::parse($payload['issued_at'] ?? 'now');
        $expiresAt = CarbonImmutable::parse($payload['expires_at'] ?? 'now');

        return new self(
            isset($payload['user_id']) ? (int) $payload['user_id'] : null,
            self::normaliseIntArray($payload['community_ids'] ?? []),
            self::normaliseIntArray($payload['unrestricted_paid_community_ids'] ?? []),
            self::normaliseIntArray($payload['subscription_tier_ids'] ?? []),
            (bool) ($payload['include_public'] ?? false),
            (bool) ($payload['include_community'] ?? false),
            (bool) ($payload['include_paid'] ?? false),
            $issuedAt,
            $expiresAt,
            (int) ($payload['version'] ?? self::CURRENT_VERSION),
        );
    }

    /**
     * @param iterable<int, mixed> $values
     * @return array<int, int>
     */
    private static function normaliseIntArray(iterable $values): array
    {
        $normalised = [];

        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $normalised[] = (int) $value;
        }

        return array_values(array_unique($normalised));
    }
}

