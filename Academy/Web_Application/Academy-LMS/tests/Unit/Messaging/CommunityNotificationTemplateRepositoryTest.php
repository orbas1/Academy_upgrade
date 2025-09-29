<?php

declare(strict_types=1);

namespace Tests\Unit\Messaging;

use App\Domain\Messaging\Templates\CommunityNotificationTemplateRepository;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Tests\TestCase;

class CommunityNotificationTemplateRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'https://example.test');
        URL::forceRootUrl('https://example.test');
        URL::forceScheme('https');
    }

    protected function tearDown(): void
    {
        URL::forceRootUrl(null);
        URL::forceScheme(null);

        parent::tearDown();
    }

    public function test_builds_email_message_with_brand_and_preferences_link(): void
    {
        $repository = app(CommunityNotificationTemplateRepository::class);

        $message = $repository->buildEmailMessage('invite', [
            'recipientName' => 'Ada Lovelace',
            'inviterName' => 'Grace Hopper',
            'community' => 'Makers Guild',
            'platform' => 'Academy',
            'actionUrl' => 'https://example.test/invitations/abc123',
            'expiryDate' => 'May 30, 2024',
        ], 'en');

        $this->assertSame('invite', $message->event);
        $this->assertSame('email.community.invite', $message->view);
        $this->assertSame('You are invited to join Makers Guild on Academy', $message->subject);
        $this->assertSame('https://example.test/email/brand-light.svg', $message->data['brand']['light_logo_url']);
        $this->assertSame('https://example.test/email/brand-dark.svg', $message->data['brand']['dark_logo_url']);
        $this->assertSame('https://example.test/settings/notifications', $message->data['preferencesUrl']);
        $this->assertSame('Ada Lovelace', $message->data['recipientName']);
    }

    public function test_builds_push_message_for_supported_locale(): void
    {
        $repository = app(CommunityNotificationTemplateRepository::class);

        $message = $repository->buildPushMessage('invite', [
            'community' => 'Makers Guild',
            'inviterName' => 'Grace Hopper',
            'recipient' => 'Ada Lovelace',
            'actionUrl' => 'https://example.test/invitations/abc123',
        ], 'es');

        $this->assertSame('invite', $message->event);
        $this->assertSame('Invitación a Makers Guild', $message->title);
        $this->assertSame('Grace Hopper te invitó a unirte. Toca para revisarla.', $message->body);
        $this->assertSame('es', $message->locale);
        $this->assertSame('Makers Guild', $message->data['community']);
        $this->assertSame('https://example.test/invitations/abc123', $message->data['deeplink']);
    }

    public function test_unknown_event_throws(): void
    {
        $repository = app(CommunityNotificationTemplateRepository::class);

        $this->expectException(InvalidArgumentException::class);
        $repository->buildEmailMessage('unknown', []);
    }
}
