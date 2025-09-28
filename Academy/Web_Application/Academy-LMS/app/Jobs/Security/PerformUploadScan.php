<?php

namespace App\Jobs\Security;

use App\Models\UploadScan;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;

class PerformUploadScan implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public UploadScan $scan)
    {
        $this->queue = config('security.uploads.queue', 'security');
    }

    public function handle(): void
    {
        $scan = $this->scan->fresh();

        if (! $scan) {
            return;
        }

        $config = config('security.uploads.scanner');
        if (! ($config['enabled'] ?? false)) {
            $scan->markSkipped('Scanner disabled.');

            return;
        }

        $command = $config['command'] ?? null;
        if (! $command || ! file_exists($scan->absolute_path)) {
            $scan->markFailed('Scanner command missing or file not found.');

            return;
        }

        $arguments = array_filter($config['arguments'] ?? []);
        $process = new Process(array_merge([$command], $arguments, [$scan->absolute_path]));
        $process->setTimeout((float) ($config['timeout'] ?? 30));
        $process->run();

        $output = trim($process->getOutput().' '.$process->getErrorOutput());

        if ($process->getExitCode() === 0) {
            $scan->markClean($output ?: 'Clean');

            return;
        }

        if ($process->getExitCode() === 1) {
            $quarantineRoot = $config['quarantine_path'] ?? storage_path('app/quarantine');
            $quarantinePath = $scan->moveToQuarantine($quarantineRoot);
            $scan->markInfected($output ?: 'Infected', $quarantinePath);

            return;
        }

        $scan->markFailed($output ?: 'Scanner failure');
    }
}
