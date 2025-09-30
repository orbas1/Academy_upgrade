<?php

declare(strict_types=1);

namespace App\Support\Logging;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;

class CloudWatchLoggerFactory
{
    public function __invoke(array $config): Logger
    {
        $name = $config['name'] ?? 'cloudwatch';
        $clientConfig = $this->resolveClientConfig($config);

        $client = new CloudWatchLogsClient($clientConfig);
        $cacheStore = $config['cache'] ?? null;
        $cache = $cacheStore ? Cache::store($cacheStore) : Cache::store();

        $handler = new CloudWatchLogsHandler(
            $client,
            $cache,
            (string) ($config['group'] ?? 'academy/app'),
            (string) ($config['stream'] ?? 'default'),
            (int) ($config['retention_days'] ?? 30),
            Logger::toMonologLevel($config['level'] ?? Logger::INFO)
        );

        $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES, true));

        $logger = new Logger($name);
        $logger->pushHandler($handler);

        return $logger;
    }

    private function resolveClientConfig(array $config): array
    {
        $client = $config['client'] ?? [];
        $client['version'] = $client['version'] ?? 'latest';
        $client['region'] = $client['region'] ?? env('AWS_DEFAULT_REGION', 'us-east-1');

        if (! Arr::exists($client, 'credentials')) {
            $key = env('AWS_ACCESS_KEY_ID');
            $secret = env('AWS_SECRET_ACCESS_KEY');

            if ($key && $secret) {
                $client['credentials'] = [
                    'key' => $key,
                    'secret' => $secret,
                ];
            }
        }

        return $client;
    }
}
