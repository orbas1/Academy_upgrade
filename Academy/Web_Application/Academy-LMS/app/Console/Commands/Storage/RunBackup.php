<?php

namespace App\Console\Commands\Storage;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\Filesystem as LocalFilesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class RunBackup extends Command
{
    protected $signature = 'storage:backup {profile=media : The lifecycle profile to use for backup configuration}';

    protected $description = 'Creates encrypted storage and database backups and pushes them to cold storage.';

    public function handle(): int
    {
        $profile = $this->argument('profile');
        $config = Config::get("storage_lifecycle.profiles.{$profile}.backup");

        if (! is_array($config) || ! Arr::get($config, 'enabled')) {
            $this->warn("Backups are disabled for profile [{$profile}].");

            return self::SUCCESS;
        }

        $disk = $this->resolveDisk($config);
        $backupId = now()->format('Ymd_His') . '_' . Str::uuid();

        $archivePath = storage_path("app/backups/{$backupId}.tar.gz");
        $this->files()->ensureDirectoryExists(dirname($archivePath));

        $paths = Arr::wrap(Arr::get($config, 'paths', []));

        $this->info('Creating storage archive...');
        $this->createArchive($archivePath, $paths);

        if (Arr::get($config, 'database.enabled')) {
            $this->info('Running database dump...');
            $dumpPath = storage_path("app/backups/{$backupId}.sql");
            $this->dumpDatabase($dumpPath, Arr::get($config, 'database.connection', 'mysql'));
            $paths[] = $dumpPath;
            $this->appendToArchive($archivePath, [$dumpPath]);
        }

        $remotePath = "backups/{$profile}/{$backupId}.tar.gz";
        $this->info("Uploading archive to {$remotePath}...");
        $stream = fopen($archivePath, 'rb');
        $disk->put($remotePath, $stream);
        fclose($stream);

        $this->info('Backup complete. Pruning old archives...');
        $this->pruneOldBackups($disk, "backups/{$profile}", Arr::get($config, 'retention_days', 30));

        $this->files()->delete($archivePath);
        foreach ($paths as $path) {
            if (str_ends_with($path, '.sql')) {
                $this->files()->delete($path);
            }
        }

        return self::SUCCESS;
    }

    private function createArchive(string $archivePath, array $paths): void
    {
        $paths = array_filter($paths, fn ($path) => $path && file_exists($path));

        if (empty($paths)) {
            throw new RuntimeException('No backup paths have been configured.');
        }

        $command = array_merge(['tar', '-czf', $archivePath], $paths);
        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Failed to create archive: ' . $process->getErrorOutput());
        }
    }

    private function appendToArchive(string $archivePath, array $paths): void
    {
        $command = array_merge(['tar', '-rf', $archivePath], $paths);
        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Failed to append to archive: ' . $process->getErrorOutput());
        }
    }

    private function dumpDatabase(string $path, string $connection): void
    {
        $config = DB::connection($connection)->getConfig();

        $command = [
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--lock-tables=false',
            '--user=' . $config['username'],
            '--password=' . ($config['password'] ?? ''),
            '--host=' . ($config['host'] ?? '127.0.0.1'),
            '--port=' . ($config['port'] ?? 3306),
            $config['database'],
        ];

        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) use ($path) {
            if ($type === Process::OUT) {
                file_put_contents($path, $buffer, FILE_APPEND);
            }
        });

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Failed to dump database: ' . $process->getErrorOutput());
        }
    }

    private function pruneOldBackups(Filesystem $disk, string $directory, int $retentionDays): void
    {
        $deadline = now()->subDays($retentionDays);

        foreach ($disk->files($directory) as $path) {
            if ($disk->lastModified($path) < $deadline->getTimestamp()) {
                $disk->delete($path);
            }
        }
    }

    private function resolveDisk(array $config): FilesystemAdapter
    {
        $diskName = Arr::get($config, 'disk', 's3-backups');

        $disk = Storage::disk($diskName);
        if (! $disk) {
            throw new RuntimeException("Backup disk [{$diskName}] could not be resolved.");
        }

        return $disk;
    }

    private function files(): LocalFilesystem
    {
        return app('files');
    }
}
