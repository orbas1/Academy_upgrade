<?php

namespace Tests\Unit\Security;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunitySubscription;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Models\CommunityNotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @group data-protection
 */
class CommunityDataEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_attributes_are_encrypted_at_rest(): void
    {
        $member = CommunityMember::factory()->create([
            'preferences' => ['dm' => true, 'digest' => 'weekly'],
            'metadata' => ['notes' => 'Contains personal context'],
        ]);

        $rawMember = DB::table('community_members')->where('id', $member->id)->first();
        $this->assertNotNull($rawMember->preferences);
        $this->assertNotSame(json_encode(['dm' => true, 'digest' => 'weekly']), $rawMember->preferences);
        $this->assertSame(['dm' => true, 'digest' => 'weekly'], $member->fresh()->preferences);
        $this->assertSame(
            ['notes' => 'Contains personal context'],
            $member->fresh()->metadata
        );
        $this->assertSame(
            ['notes' => 'Contains personal context'],
            $this->decryptEncryptedJson($rawMember->metadata)
        );

        $community = Community::factory()->create();
        $user = User::factory()->create();
        $tier = CommunitySubscriptionTier::factory()->create([
            'community_id' => $community->id,
        ]);

        $subscription = CommunitySubscription::create([
            'community_id' => $community->id,
            'user_id' => $user->id,
            'subscription_tier_id' => $tier->id,
            'provider' => 'stripe',
            'status' => 'active',
            'metadata' => ['payment_method' => 'stripe', 'country' => 'DE'],
        ]);

        $rawSubscription = DB::table('community_subscriptions')->where('id', $subscription->id)->first();
        $this->assertNotSame(json_encode(['payment_method' => 'stripe', 'country' => 'DE']), $rawSubscription->metadata);
        $this->assertSame(
            ['payment_method' => 'stripe', 'country' => 'DE'],
            $subscription->fresh()->metadata
        );
        $this->assertSame(
            ['payment_method' => 'stripe', 'country' => 'DE'],
            $this->decryptEncryptedJson($rawSubscription->metadata)
        );

        $preference = CommunityNotificationPreference::create([
            'user_id' => $user->id,
            'channel_email' => true,
            'channel_push' => true,
            'channel_in_app' => true,
            'digest_frequency' => 'daily',
            'metadata' => ['timezone' => 'Europe/Berlin'],
        ]);
        $rawPreference = DB::table('community_notification_preferences')->where('id', $preference->id)->first();
        $this->assertNotNull($rawPreference->metadata);
        $this->assertNotSame(json_encode(['timezone' => 'Europe/Berlin']), $rawPreference->metadata);
        $this->assertSame(['timezone' => 'Europe/Berlin'], $preference->fresh()->metadata);
        $this->assertSame(['timezone' => 'Europe/Berlin'], $this->decryptEncryptedJson($rawPreference->metadata));
    }

    private function decryptEncryptedJson(?string $value): array
    {
        $this->assertNotNull($value);

        $decoded = json_decode($value, true);

        if (is_array($decoded) && isset($decoded['ciphertext'])) {
            $cipher = $decoded['ciphertext'];
        } elseif (is_string($decoded)) {
            $cipher = $decoded;
        } else {
            $cipher = $value;
        }

        return json_decode(Crypt::decryptString($cipher), true, 512, \JSON_THROW_ON_ERROR);
    }
}
