<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HandlesApiExceptionTest extends TestCase
{
    public function test_validation_exception_returns_enveloped_response(): void
    {
        /** @var Handler $handler */
        $handler = app(Handler::class);

        $request = Request::create('/api/v1/example', 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->app->instance('request', $request);

        $exception = ValidationException::withMessages([
            'name' => ['The name field is required.'],
        ]);

        $response = $handler->render($request, $exception);
        $payload = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('errors', $payload);
        $this->assertSame('validation.name', $payload['errors'][0]['code']);
        $this->assertArrayHasKey('request_id', $payload['meta']);
    }
}
