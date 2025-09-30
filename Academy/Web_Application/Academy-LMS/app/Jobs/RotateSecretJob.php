<?php

namespace App\Jobs;

use App\Support\Secrets\SecretManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RotateSecretJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'webhooks';

    public function __construct(public readonly string $key, public readonly ?string $driver = null)
    {
    }

    public function handle(SecretManager $secrets): void
    {
        try {
            $result = $secrets->rotate($this->key, $this->driver);

            Log::info('Secret rotated via job', [
                'key' => $this->key,
                'driver' => $this->driver,
                'version' => $result->version,
            ]);
        } catch (Throwable $throwable) {
            Log::error('Secret rotation job failed', [
                'key' => $this->key,
                'driver' => $this->driver,
                'message' => $throwable->getMessage(),
            ]);

            $this->fail($throwable);
        }
    }
}
