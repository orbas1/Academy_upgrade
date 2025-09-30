<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Observability\CorrelationIdStore;
use App\Support\Observability\Metrics\PrometheusRegistry;
use App\Support\Observability\Metrics\StatsdClient;
use App\Support\Observability\Metrics\UdpMetricTransport;
use App\Support\Observability\ObservabilityManager;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\ServiceProvider;

class ObservabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CorrelationIdStore::class, function ($app) {
            $log = $app['log'];

            $updateContext = function (?string $correlationId) use ($log): void {
                $log->shareContext(array_filter([
                    'application' => config('app.name'),
                    'environment' => config('app.env'),
                    'correlation_id' => $correlationId,
                ]));
            };

            $store = new CorrelationIdStore($updateContext);
            $updateContext($store->get());

            return $store;
        });

        $this->app->singleton(StatsdClient::class, function ($app) {
            $config = $app['config']->get('observability.metrics');

            $transport = new UdpMetricTransport(
                $config['host'],
                (int) $config['port'],
                (float) $config['timeout']
            );

            return new StatsdClient(
                $transport,
                (string) $config['prefix'],
                (bool) $config['enabled'],
                (array) ($config['default_tags'] ?? [])
            );
        });

        $this->app->singleton(PrometheusRegistry::class, function ($app) {
            $config = $app['config']->get('observability.prometheus', []);
            $store = $config['store']
                ? $app->make(CacheFactory::class)->store($config['store'])
                : $app->make(CacheFactory::class)->store();

            return new PrometheusRegistry($store, $config);
        });

        $this->app->singleton(ObservabilityManager::class, function ($app) {
            $config = $app['config']->get('observability', []);
            $logManager = $app['log'];
            $channel = $config['logging']['channel'] ?? null;
            $defaultChannel = $logManager->getDefaultDriver();
            $logger = $channel
                ? $logManager->channel($channel)
                : $logManager->channel($defaultChannel);

            return new ObservabilityManager(
                $app->make(StatsdClient::class),
                $logger,
                $app->make(PrometheusRegistry::class),
                $config
            );
        });
    }

    public function boot(Dispatcher $events): void
    {
        $config = $this->app['config']->get('observability.logging', []);
        if (($config['share_context'] ?? true) === true) {
            $store = $this->app->make(CorrelationIdStore::class);
            $this->app['log']->shareContext(array_filter([
                'application' => config('app.name'),
                'environment' => config('app.env'),
                'correlation_id' => $store->get(),
            ]));
        }

        $manager = $this->app->make(ObservabilityManager::class);

        $events->listen(JobProcessed::class, static function (JobProcessed $event) use ($manager): void {
            $jobName = method_exists($event->job, 'resolveName')
                ? $event->job->resolveName()
                : $event->job::class;

            $manager->recordQueueJob(
                $jobName,
                $event->connectionName,
                method_exists($event->job, 'getQueue') ? $event->job->getQueue() : null,
                $event->time
            );
        });

        $events->listen(JobProcessing::class, static function (JobProcessing $event) use ($manager): void {
            $jobName = method_exists($event->job, 'resolveName')
                ? $event->job->resolveName()
                : $event->job::class;

            $payload = method_exists($event->job, 'payload') ? $event->job->payload() : [];
            $availableAt = $payload['available_at'] ?? null;

            if (is_numeric($availableAt)) {
                $lagSeconds = max(0.0, microtime(true) - (int) $availableAt);
                $manager->recordQueueLag(
                    $jobName,
                    $event->connectionName,
                    method_exists($event->job, 'getQueue') ? $event->job->getQueue() : null,
                    $lagSeconds
                );
            }
        });

        $events->listen(JobFailed::class, static function (JobFailed $event) use ($manager): void {
            $jobName = method_exists($event->job, 'resolveName')
                ? $event->job->resolveName()
                : $event->job::class;

            $manager->recordQueueFailure(
                $jobName,
                $event->connectionName,
                method_exists($event->job, 'getQueue') ? $event->job->getQueue() : null,
                $event->exception
            );
        });

        $events->listen(QueryExecuted::class, static function (QueryExecuted $event) use ($manager): void {
            $manager->recordDatabaseQuery(
                $event->connectionName,
                $event->sql,
                $event->time
            );
        });
    }
}
