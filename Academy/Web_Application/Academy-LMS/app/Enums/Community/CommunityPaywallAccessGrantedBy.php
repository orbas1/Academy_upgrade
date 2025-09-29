<?php
declare(strict_types=1);


namespace App\Enums\Community;

enum CommunityPaywallAccessGrantedBy: string
{
    case TIER = 'tier';
    case SINGLE_PURCHASE = 'single_purchase';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::TIER => 'communities.paywall.granted_by.tier',
            self::SINGLE_PURCHASE => 'communities.paywall.granted_by.single_purchase',
            self::ADMIN => 'communities.paywall.granted_by.admin',
        };
    }
}
