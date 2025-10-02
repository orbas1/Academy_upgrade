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
use InvalidArgumentException;
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

        /** @var array<int, string> $paths */
        $paths = array_values(array_filter(
            Arr::wrap(Arr::get($config, 'paths', [])),
            fn ($path): bool => is_string($path) && $path !== ''
        ));

        $this->info('Creating storage archive...');
        $this->createArchive($archivePath, $paths);

        if (Arr::get($config, 'database.enabled')) {
            $this->info('Running database dump...');
            $dumpPath = storage_path("app/backups/{$backupId}.sql");
            $this->dumpDatabase($dumpPath, Arr::get($config, 'database.connection', 'mysql'));
            $paths[] = $dumpPath;
            $this->appendToArchive($archivePath, [$dumpPath]);
        }

        $archivePath = $this->maybeEncryptArchive($archivePath, $config);

        $remotePath = "backups/{$profile}/" . basename($archivePath);
        $this->info("Uploading archive to {$remotePath}...");
        $stream = fopen($archivePath, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Unable to open archive for reading.');
        }

        try {
            $disk->put($remotePath, $stream);
        } finally {
            fclose($stream);
        }

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

    /**
     * @param array<int, string> $paths
     */
    private function createArchive(string $archivePath, array $paths): void
    {
        $validPaths = array_filter($paths, static fn (string $path): bool => file_exists($path));

        if (empty($validPaths)) {
            throw new RuntimeException('No backup paths have been configured.');
        }

        $command = array_merge(['tar', '-czf', $archivePath], array_values($validPaths));
        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Failed to create archive: ' . $process->getErrorOutput());
        }
    }

    /**
     * @param array<int, string> $paths
     */
    private function appendToArchive(string $archivePath, array $paths): void
    {
        $command = array_merge(['tar', '-rf', $archivePath], array_values($paths));
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

    /**
     * @param array<string, mixed> $config
     */
    private function maybeEncryptArchive(string $archivePath, array $config): string
    {
        $encryption = Arr::get($config, 'encryption');

        if (! is_array($encryption) || empty($encryption['enabled'])) {
            return $archivePath;
        }

        $key = $encryption['key'] ?? env('STORAGE_BACKUP_ENCRYPTION_KEY');
        if (! $key) {
            throw new RuntimeException('Backup encryption is enabled but no encryption key has been provided.');
        }

        $cipher = $encryption['cipher'] ?? 'aes-256-cbc';
        $encryptedPath = $archivePath . '.enc';

        $process = new Process([
            'openssl',
            'enc',
            '-' . $cipher,
            '-salt',
            '-pbkdf2',
            '-pass',
            'pass:' . $key,
            '-in',
            $archivePath,
            '-out',
            $encryptedPath,
        ]);

        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Failed to encrypt backup: ' . $process->getErrorOutput());
        }

        $this->files()->delete($archivePath);

        return $encryptedPath;
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

    /**
     * @param array<string, mixed> $config
     */
    private function resolveDisk(array $config): FilesystemAdapter
    {
        $diskName = Arr::get($config, 'disk', 's3-backups');

        try {
            return Storage::disk($diskName);
        } catch (InvalidArgumentException $exception) {
            throw new RuntimeException("Backup disk [{$diskName}] could not be resolved.", 0, $exception);
        }
    }

    private function files(): LocalFilesystem
    {
        return app('files');
    }
}
