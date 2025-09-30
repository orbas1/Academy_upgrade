<?php

namespace App\Services\Systemd;

use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Process\Process;

class SystemdServiceManager
{
    private string $binary;
    private string $reloadCommand;
    private bool $requireRoot;

    public function __construct(private readonly LoggerInterface $logger)
    {
        $this->binary = (string) config('queue-autoscale.systemd_binary', '/bin/systemctl');
        $this->reloadCommand = (string) config('queue-autoscale.reload_command', 'reload-or-restart');
        $this->requireRoot = (bool) config('queue-autoscale.require_root', true);
    }

    public function isSupported(): bool
    {
        if (! is_executable($this->binary)) {
            $this->logger->warning('systemd binary not found; skipping queue worker reloads.', ['binary' => $this->binary]);

            return false;
        }

        if ($this->requireRoot && (! function_exists('posix_geteuid') || posix_geteuid() !== 0)) {
            $this->logger->warning('systemd operations require root privileges; skipping reload.');

            return false;
        }

        return true;
    }

    public function reload(string $unit): void
    {
        if (! $this->isSupported()) {
            return;
        }

        $status = $this->runCommand([$this->binary, 'is-active', '--quiet', $unit]);

        if ($status !== 0) {
            $this->logger->info('systemd unit inactive; attempting start before reload.', ['unit' => $unit]);
            $startStatus = $this->runCommand([$this->binary, 'start', $unit]);

            if ($startStatus !== 0) {
                throw new RuntimeException(sprintf('Failed to start systemd unit [%s]', $unit));
            }

            return;
        }

        $result = $this->runCommand([$this->binary, $this->reloadCommand, $unit]);

        if ($result !== 0) {
            throw new RuntimeException(sprintf('Failed to reload systemd unit [%s]', $unit));
        }
    }

    /**
     * @param  array<int, string>  $command
     */
    private function runCommand(array $command): int
    {
        $process = new Process($command);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->logger->warning('systemd command failed', [
                'command' => Arr::join($command, ' '),
                'exit_code' => $process->getExitCode(),
                'error_output' => $process->getErrorOutput(),
            ]);
        }

        return $process->getExitCode() ?? 1;
    }
}
