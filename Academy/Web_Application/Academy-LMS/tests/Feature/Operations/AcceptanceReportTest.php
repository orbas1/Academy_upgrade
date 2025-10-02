<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @group data-protection
 */
final class AcceptanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_retrieve_acceptance_report(): void
    {
        $user = User::factory()->create([
            'role' => 'owner',
        ]);

        Sanctum::actingAs($user);
        auth()->setUser($user);

        /** @var \App\Http\Controllers\Api\V1\Ops\AcceptanceReportController $controller */
        $controller = $this->app->make(\App\Http\Controllers\Api\V1\Ops\AcceptanceReportController::class);

        $response = $controller();

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true)['data'] ?? [];

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('summary', $payload);
        $this->assertArrayHasKey('requirements', $payload);
        $this->assertNotEmpty($payload['requirements']);
        $this->assertSame(
            count($payload['requirements']),
            $payload['summary']['requirements_total'] ?? null
        );

        foreach ($payload['requirements'] as $requirement) {
            $this->assertArrayHasKey('id', $requirement);
            $this->assertArrayHasKey('title', $requirement);
            $this->assertArrayHasKey('status', $requirement);
            $this->assertArrayHasKey('completion', $requirement);
            $this->assertArrayHasKey('quality', $requirement);
        }
    }
}
