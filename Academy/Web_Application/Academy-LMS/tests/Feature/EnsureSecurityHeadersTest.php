<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureSecurityHeaders;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnsureSecurityHeadersTest extends TestCase
{
    public function test_default_security_headers_are_applied(): void
    {
        $middleware = new EnsureSecurityHeaders();
        $request = Request::create('/test/security/default', 'GET');

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame('max-age=63072000; includeSubDomains; preload', $response->headers->get('Strict-Transport-Security'));
        $this->assertSame(
            "default-src 'self'; img-src 'self' data: https:; media-src https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.*; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; connect-src 'self' https: wss:; frame-ancestors 'none'; base-uri 'self'",
            $response->headers->get('Content-Security-Policy')
        );
        $this->assertSame('geolocation=(self), camera=(), microphone=()', $response->headers->get('Permissions-Policy'));
    }

    public function test_profile_overrides_can_adjust_headers(): void
    {
        config()->set('security-headers.profiles.integration-test', [
            'Content-Security-Policy' => "default-src 'self' https://example.com; frame-ancestors 'self' https://example.com;",
            'X-Frame-Options' => null,
        ]);

        $middleware = new EnsureSecurityHeaders();
        $request = Request::create('/test/security/profile', 'GET');

        $response = $middleware->handle($request, function (Request $innerRequest) use ($middleware) {
            return $middleware->handle($innerRequest, fn () => response('ok'), 'integration-test');
        });

        $this->assertSame(
            "default-src 'self' https://example.com; frame-ancestors 'self' https://example.com;",
            $response->headers->get('Content-Security-Policy')
        );
        $this->assertFalse($response->headers->has('X-Frame-Options'));
    }

    public function test_requests_marked_to_skip_headers_are_respected(): void
    {
        $middleware = new EnsureSecurityHeaders();
        $request = Request::create('/test/security/skip', 'GET');

        $response = $middleware->handle($request, function (Request $innerRequest) {
            EnsureSecurityHeaders::skip($innerRequest);

            return response('ok');
        });

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
    }

    public function test_api_profile_is_detected_for_json_requests(): void
    {
        $middleware = new EnsureSecurityHeaders();
        $request = Request::create('/api/v1/communities', 'GET', server: [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(config('security-headers.profiles.api.Content-Security-Policy'), $response->headers->get('Content-Security-Policy'));
        $this->assertSame('cross-origin', $response->headers->get('Cross-Origin-Resource-Policy'));
        $this->assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
    }

    public function test_mobile_profile_is_selected_when_client_header_present(): void
    {
        $middleware = new EnsureSecurityHeaders();
        $request = Request::create('/api/v1/communities/feed', 'GET', server: [
            'HTTP_X_ACADEMY_CLIENT' => 'mobile-app/android; version=1.2.3; env=staging',
        ]);

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(config('security-headers.profiles.mobile-api.Content-Security-Policy'), $response->headers->get('Content-Security-Policy'));
        $this->assertSame(config('security-headers.profiles.mobile-api.Permissions-Policy'), $response->headers->get('Permissions-Policy'));
        $this->assertSame('cross-origin', $response->headers->get('Cross-Origin-Resource-Policy'));
    }
}
