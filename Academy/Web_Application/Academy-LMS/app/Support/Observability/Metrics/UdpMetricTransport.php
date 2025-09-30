<?php

declare(strict_types=1);

namespace App\Support\Observability\Metrics;

use RuntimeException;

class UdpMetricTransport implements MetricTransport
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly float $timeout = 0.2
    ) {
    }

    public function send(string $payload): void
    {
        if ($payload === '') {
            return;
        }

        $address = sprintf('udp://%s:%d', $this->host, $this->port);
        $errno = 0;
        $errstr = '';

        $socket = @stream_socket_client($address, $errno, $errstr, $this->timeout);

        if (! $socket) {
            throw new RuntimeException(sprintf('Unable to send metric payload: %s', $errstr ?: 'unknown error'));
        }

        stream_set_timeout($socket, 0, (int) ($this->timeout * 1_000_000));
        @fwrite($socket, $payload);
        fclose($socket);
    }
}
