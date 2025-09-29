<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Http;

use App\Support\Http\ApiResponseBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ApiResponseBuilderTest extends TestCase
{
    public function test_success_wraps_payload_with_meta(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2024, 1, 1, 0, 0, 0, 'UTC'));

        $builder = new ApiResponseBuilder('req-123', 'UTC');

        $response = $builder->success(['message' => 'ok'], ['foo' => 'bar'], 201);
        $payload = $response->getData(true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('ok', $payload['data']['message']);
        $this->assertSame('bar', $payload['meta']['foo']);
        $this->assertSame('req-123', $payload['meta']['request_id']);
        $this->assertSame('2024-01-01T00:00:00+00:00', $payload['meta']['timestamp']);
    }

    public function test_paginated_response_includes_pagination_meta(): void
    {
        $builder = new ApiResponseBuilder('req-456', 'UTC');

        $paginator = new LengthAwarePaginator(
            collect([
                ['id' => 1],
                ['id' => 2],
            ]),
            10,
            2,
            2,
            ['path' => 'http://example.test/items']
        );

        $response = $builder->paginated($paginator);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(2, $payload['meta']['pagination']['current_page']);
        $this->assertSame(10, $payload['meta']['pagination']['total']);
        $this->assertSame(
            'http://example.test/items?page=3',
            $payload['meta']['pagination']['links']['next']
        );
    }

    public function test_error_wraps_validation_messages(): void
    {
        $builder = new ApiResponseBuilder('req-err', 'UTC');

        $response = $builder->error('Validation Failed', 'The data is invalid.', 422, [
            'email' => ['The email field is required.'],
        ]);

        $payload = $response->getData(true);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('errors', $payload);
        $this->assertSame('validation.email', $payload['errors'][0]['code']);
        $this->assertSame('/data/attributes/email', $payload['errors'][0]['source']['pointer']);
        $this->assertSame('req-err', $payload['meta']['request_id']);
    }
}
