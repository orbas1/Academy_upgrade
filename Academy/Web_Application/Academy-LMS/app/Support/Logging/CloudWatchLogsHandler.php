<?php

declare(strict_types=1);

namespace App\Support\Logging;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\Exception\AwsException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use RuntimeException;
use Throwable;

class CloudWatchLogsHandler extends AbstractProcessingHandler
{
    private bool $initialized = false;

    private readonly string $sequenceTokenCacheKey;

    public function __construct(
        private readonly CloudWatchLogsClient $client,
        private readonly CacheRepository $cache,
        private readonly string $logGroup,
        private readonly string $logStream,
        private readonly int $retentionDays = 30,
        int $level = Level::Info,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);

        $this->sequenceTokenCacheKey = sprintf('observability:cloudwatch:%s:%s:token', $this->logGroup, $this->logStream);
    }

    protected function write(LogRecord $record): void
    {
        $this->initialiseIfRequired();

        $payload = [
            'logGroupName' => $this->logGroup,
            'logStreamName' => $this->logStream,
            'logEvents' => [[
                'timestamp' => $this->timestampFromRecord($record),
                'message' => (string) $record->formatted,
            ]],
        ];

        $token = $this->cache->get($this->sequenceTokenCacheKey);
        if (is_string($token) && $token !== '') {
            $payload['sequenceToken'] = $token;
        }

        $this->publishWithRetry($payload, 0);
    }

    private function publishWithRetry(array $payload, int $attempt): void
    {
        try {
            $result = $this->client->putLogEvents($payload);
            $nextToken = $result->get('nextSequenceToken');
            if (is_string($nextToken) && $nextToken !== '') {
                $this->cache->put($this->sequenceTokenCacheKey, $nextToken, now()->addHours(6));
            }
        } catch (AwsException $exception) {
            if ($attempt >= 3) {
                throw $exception;
            }

            $code = $exception->getAwsErrorCode();
            if ($code === 'InvalidSequenceTokenException' || $code === 'DataAlreadyAcceptedException') {
                $payload['sequenceToken'] = $this->refreshSequenceToken();
                $this->publishWithRetry($payload, $attempt + 1);

                return;
            }

            if ($code === 'ResourceNotFoundException') {
                $this->initialized = false;
                $this->initialiseIfRequired();
                $this->publishWithRetry($payload, $attempt + 1);

                return;
            }

            throw $exception;
        } catch (Throwable $throwable) {
            if ($attempt >= 1) {
                throw $throwable;
            }

            $this->initialized = false;
            $this->initialiseIfRequired();
            $this->publishWithRetry($payload, $attempt + 1);
        }
    }

    private function initialiseIfRequired(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->ensureLogGroupExists();
        $this->ensureLogStreamExists();
        $this->initialized = true;
    }

    private function ensureLogGroupExists(): void
    {
        try {
            $groups = $this->client->describeLogGroups([
                'logGroupNamePrefix' => $this->logGroup,
                'limit' => 1,
            ]);

            $existing = collect($groups->get('logGroups') ?? [])
                ->firstWhere('logGroupName', $this->logGroup);

            if (! $existing) {
                $this->client->createLogGroup(['logGroupName' => $this->logGroup]);
                if ($this->retentionDays > 0) {
                    $this->client->putRetentionPolicy([
                        'logGroupName' => $this->logGroup,
                        'retentionInDays' => $this->retentionDays,
                    ]);
                }
            }
        } catch (AwsException $exception) {
            throw new RuntimeException('Unable to ensure CloudWatch log group exists: '.$exception->getMessage(), previous: $exception);
        }
    }

    private function ensureLogStreamExists(): void
    {
        try {
            $streams = $this->client->describeLogStreams([
                'logGroupName' => $this->logGroup,
                'logStreamNamePrefix' => $this->logStream,
                'limit' => 1,
            ]);

            $existing = collect($streams->get('logStreams') ?? [])
                ->firstWhere('logStreamName', $this->logStream);

            if (! $existing) {
                $this->client->createLogStream([
                    'logGroupName' => $this->logGroup,
                    'logStreamName' => $this->logStream,
                ]);
            } else {
                $token = $existing['uploadSequenceToken'] ?? null;
                if (is_string($token) && $token !== '') {
                    $this->cache->put($this->sequenceTokenCacheKey, $token, now()->addHours(6));
                }
            }
        } catch (AwsException $exception) {
            throw new RuntimeException('Unable to ensure CloudWatch log stream exists: '.$exception->getMessage(), previous: $exception);
        }
    }

    private function refreshSequenceToken(): ?string
    {
        try {
            $streams = $this->client->describeLogStreams([
                'logGroupName' => $this->logGroup,
                'logStreamNamePrefix' => $this->logStream,
                'limit' => 1,
            ]);
        } catch (AwsException $exception) {
            throw new RuntimeException('Unable to refresh CloudWatch sequence token: '.$exception->getMessage(), previous: $exception);
        }

        $stream = collect($streams->get('logStreams') ?? [])
            ->firstWhere('logStreamName', $this->logStream);

        $token = $stream['uploadSequenceToken'] ?? null;
        if (is_string($token) && $token !== '') {
            $this->cache->put($this->sequenceTokenCacheKey, $token, now()->addHours(6));

            return $token;
        }

        $this->cache->forget($this->sequenceTokenCacheKey);

        return null;
    }

    private function timestampFromRecord(LogRecord $record): int
    {
        $micro = $record->datetime->format('U.u');
        $seconds = (float) $micro;

        return (int) floor($seconds * 1000);
    }
}
