<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebConfig
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $s3_keys = get_settings('amazon_s3', 'object');
        $s3Settings = (array) $s3_keys;
        $resolve = static function (array $settings, string $primary, string $fallback) {
            return $settings[$primary] ?? $settings[$fallback] ?? null;
        };

        config(
            [
                'app.name' => get_settings('system_title'),
                'app.timezone' => get_settings('timezone'),

                // //SMTP configuration
                'mail.mailers.smtp.transport' => get_settings('protocol'),
                'mail.mailers.smtp.host' => get_settings('smtp_host'),
                'mail.mailers.smtp.port' => get_settings('smtp_port'),
                'mail.mailers.smtp.encryption' => get_settings('smtp_crypto'),
                'mail.mailers.smtp.username' => get_settings('smtp_user'),
                'mail.mailers.smtp.password' => get_settings('smtp_pass'),
                'mail.mailers.smtp.timeout' => null,
                'mail.mailers.smtp.local_domain' => $_SERVER['SERVER_NAME'],
                'mail.from.name' => get_settings('system_title'),
                'mail.from.address' => get_settings('smtp_from_email'),

                'filesystems.disks.s3.key' => $resolve($s3Settings, 'CLOUDFLARE_R2_ACCESS_KEY_ID', 'AWS_ACCESS_KEY_ID'),
                'filesystems.disks.s3.secret' => $resolve($s3Settings, 'CLOUDFLARE_R2_SECRET_ACCESS_KEY', 'AWS_SECRET_ACCESS_KEY'),
                'filesystems.disks.s3.region' => $resolve($s3Settings, 'CLOUDFLARE_R2_DEFAULT_REGION', 'AWS_DEFAULT_REGION'),
                'filesystems.disks.s3.bucket' => $resolve($s3Settings, 'CLOUDFLARE_R2_BUCKET', 'AWS_BUCKET'),
            ]
        );

        return $next($request);
    }
}
