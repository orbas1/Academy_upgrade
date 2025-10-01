<?php

declare(strict_types=1);

namespace App\Domain\Communities\DTO;

final class CommunityLoadTestOptions
{
    public function __construct(
        public readonly int $communityCount,
        public readonly int $membersPerCommunity,
        public readonly int $postsPerMember,
        public readonly int $commentsPerPost,
        public readonly int $reactionsPerPost,
        public readonly int $pointsEventsPerMember,
        public readonly int $tokensPerCommunity,
        public readonly bool $seedProfileActivity,
        public readonly string $ownerPassword,
        public readonly string $memberPassword,
    ) {
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        $communityCount = self::clampPositive((int) ($config['community_count'] ?? 1), 1);
        $membersPerCommunity = self::clampPositive((int) ($config['members_per_community'] ?? 50), 1);
        $postsPerMember = self::clampPositive((int) ($config['posts_per_member'] ?? 4), 0);
        $commentsPerPost = self::clampPositive((int) ($config['comments_per_post'] ?? 6), 0);
        $reactionsPerPost = self::clampPositive((int) ($config['reactions_per_post'] ?? 12), 0);
        $pointsEventsPerMember = self::clampPositive((int) ($config['points_events_per_member'] ?? 3), 0);
        $tokensPerCommunity = self::clampPositive((int) ($config['tokens_per_community'] ?? 10), 0);
        $seedProfileActivity = array_key_exists('seed_profile_activity', $config)
            ? (bool) $config['seed_profile_activity']
            : true;
        $ownerPassword = trim((string) ($config['owner_password'] ?? 'Owner#Load123!'));
        $memberPassword = trim((string) ($config['member_password'] ?? 'Member#Load123!'));

        if ($ownerPassword === '') {
            $ownerPassword = 'Owner#Load123!';
        }

        if ($memberPassword === '') {
            $memberPassword = 'Member#Load123!';
        }

        return new self(
            communityCount: $communityCount,
            membersPerCommunity: $membersPerCommunity,
            postsPerMember: $postsPerMember,
            commentsPerPost: $commentsPerPost,
            reactionsPerPost: $reactionsPerPost,
            pointsEventsPerMember: $pointsEventsPerMember,
            tokensPerCommunity: $tokensPerCommunity,
            seedProfileActivity: $seedProfileActivity,
            ownerPassword: $ownerPassword,
            memberPassword: $memberPassword,
        );
    }

    /**
     * @return array<string, int|bool|string>
     */
    public function toArray(): array
    {
        return [
            'community_count' => $this->communityCount,
            'members_per_community' => $this->membersPerCommunity,
            'posts_per_member' => $this->postsPerMember,
            'comments_per_post' => $this->commentsPerPost,
            'reactions_per_post' => $this->reactionsPerPost,
            'points_events_per_member' => $this->pointsEventsPerMember,
            'tokens_per_community' => $this->tokensPerCommunity,
            'seed_profile_activity' => $this->seedProfileActivity,
            'owner_password' => $this->ownerPassword,
            'member_password' => $this->memberPassword,
        ];
    }

    private static function clampPositive(int $value, int $minimum): int
    {
        return max($minimum, $value);
    }
}
