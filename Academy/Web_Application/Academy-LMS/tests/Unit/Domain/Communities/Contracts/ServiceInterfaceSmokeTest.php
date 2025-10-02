<?php

namespace Tests\Unit\Domain\Communities\Contracts;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class ServiceInterfaceSmokeTest extends TestCase
{
    public static function interfaceProvider(): array
    {
        return [
            ['App\\Domain\\Communities\\Contracts\\MembershipService'],
            ['App\\Domain\\Communities\\Contracts\\FeedService'],
            ['App\\Domain\\Communities\\Contracts\\PostService'],
            ['App\\Domain\\Communities\\Contracts\\CommentService'],
            ['App\\Domain\\Communities\\Contracts\\LikeService'],
            ['App\\Domain\\Communities\\Contracts\\PointsService'],
            ['App\\Domain\\Communities\\Contracts\\LeaderboardService'],
            ['App\\Domain\\Communities\\Contracts\\GeoService'],
            ['App\\Domain\\Communities\\Contracts\\SubscriptionService'],
            ['App\\Domain\\Communities\\Contracts\\PaywallService'],
            ['App\\Domain\\Communities\\Contracts\\CalendarService'],
            ['App\\Domain\\Communities\\Contracts\\ClassroomLinkService'],
        ];
    }

    /**
     * @param  class-string  $interface
     * @dataProvider interfaceProvider
     */
    public function testInterfaceIsLoadable(string $interface): void
    {
        $this->assertTrue(interface_exists($interface), sprintf('Expected %s to be defined.', $interface));
    }
}
