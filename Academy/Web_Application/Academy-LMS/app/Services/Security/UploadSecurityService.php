<?php

namespace App\Services\Security;

use App\Exceptions\Security\InfectedUploadException;
use App\Exceptions\Security\ScanFailedException;
use App\Exceptions\Security\UnsafeFileException;
use App\Jobs\Security\PerformUploadScan;
use App\Models\UploadScan;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Throwable;

class UploadSecurityService
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly Filesystem $files,
        private readonly BusDispatcher $bus
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function secureLegacyUpload(UploadedFile $uploadedFile, string $uploadTo, array $options = []): string
    {
        $this->ensureExtensionsEnabled();
        $this->validateSize($uploadedFile, $options);
        $this->validateMime($uploadedFile, $options);

        $target = $this->determineTarget($uploadedFile, $uploadTo, $options);
        $this->files->ensureDirectoryExists(dirname($target['absolute_path']));

        $tmpPath = $this->writeSanitizedFile($uploadedFile, $target['extension'], $options);

        if (! @rename($tmpPath, $target['absolute_path'])) {
            @unlink($tmpPath);

            throw new UnsafeFileException('Unable to move uploaded file into place.');
        }

        if ($target['optimized_path']) {
            $this->createOptimizedVariant($target, $options);
        }

        $scan = UploadScan::create([
            'path' => $target['relative_path'],
            'absolute_path' => $target['absolute_path'],
            'mime_type' => $uploadedFile->getMimeType(),
            'status' => UploadScan::STATUS_PENDING,
        ]);

        $scannerConfig = $this->config->get('security.uploads.scanner');

        if (! ($scannerConfig['enabled'] ?? true)) {
            $scan->markSkipped('Scanner disabled.');

            return $target['relative_path'];
        }

        $job = new PerformUploadScan($scan);
        $blockUntilClean = (bool) $this->config->get('security.uploads.block_until_clean', true);

        if ($blockUntilClean) {
            $this->bus->dispatchSync($job);
            $fresh = $scan->fresh();

            if (! $fresh) {
                throw new ScanFailedException('Unable to read scan status.');
            }

            if ($fresh->scanIsInfected()) {
                Session::flash('error', __('The uploaded file was quarantined due to malware detection.'));

                throw new InfectedUploadException($fresh);
            }

            if ($fresh->scanIsFailed()) {
                Session::flash('error', __('We could not verify the uploaded file. Please try again later.'));

                throw new ScanFailedException('Malware scan failed.');
            }
        } else {
            $this->bus->dispatch($job);
        }

        return $target['relative_path'];
    }

    private function ensureExtensionsEnabled(): void
    {
        foreach (['fileinfo', 'exif'] as $extension) {
            if (! extension_loaded($extension)) {
                throw new UnsafeFileException(sprintf('Required PHP extension "%s" is not enabled.', $extension));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function validateSize(UploadedFile $file, array $options = []): void
    {
        $limit = (int) ($options['max_size'] ?? $this->config->get('security.uploads.max_size'));

        if ($limit > 0 && $file->getSize() > $limit) {
            throw new UnsafeFileException(__('The uploaded file exceeds the maximum allowed size.'));
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function validateMime(UploadedFile $file, array $options = []): void
    {
        $allowed = Arr::wrap($options['allowed_mimes'] ?? $this->config->get('security.uploads.allowed_mimes'));
        $mime = $file->getMimeType();

        if (empty($allowed) || ! $mime) {
            return;
        }

        if (! in_array($mime, $allowed, true)) {
            throw new UnsafeFileException(__('This file type is not permitted.'));
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{relative_path: string, absolute_path: string, extension: string, optimized_path: ?string}
     */
    private function determineTarget(UploadedFile $file, string $uploadTo, array $options = []): array
    {
        $uploadTo = str_replace('public/', '', $uploadTo);
        $uploadTo = ltrim($uploadTo, '/');
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');

        if (! empty($options['force_extension'])) {
            $extension = $options['force_extension'];
        }

        $isDirectory = str_ends_with($uploadTo, '/') || ! pathinfo($uploadTo, PATHINFO_EXTENSION);

        if ($isDirectory) {
            $fileName = sprintf('%s-%s.%s', time(), Str::random(12), $extension);
            $relative = trim($uploadTo, '/');
            if ($relative !== '') {
                $relative .= '/'.$fileName;
            } else {
                $relative = $fileName;
            }
        } else {
            $relative = $uploadTo;
        }

        $absolute = public_path($relative);
        $this->files->ensureDirectoryExists(dirname($absolute));

        $optimizedPath = null;
        if (! empty($options['optimized_width'])) {
            $optimizedPath = dirname($absolute).DIRECTORY_SEPARATOR.'optimized'.DIRECTORY_SEPARATOR.basename($absolute);
            $this->files->ensureDirectoryExists(dirname($optimizedPath));
        }

        return [
            'relative_path' => $relative,
            'absolute_path' => $absolute,
            'extension' => $extension,
            'optimized_path' => $optimizedPath,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function writeSanitizedFile(UploadedFile $file, string $extension, array $options = []): string
    {
        $tmpDirectory = storage_path('app/uploads/tmp');
        $this->files->ensureDirectoryExists($tmpDirectory);
        $tmpPath = $tmpDirectory.DIRECTORY_SEPARATOR.Str::uuid().'.'.$extension;

        $mime = $file->getMimeType();
        $isImage = $mime && str_starts_with($mime, 'image/');

        try {
            if ($isImage) {
                $image = Image::make($file->getRealPath())->orientate();
                if (! empty($options['resize_width'])) {
                    $image->resize($options['resize_width'], $options['resize_height'] ?? null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                $image->encode($extension, (int) $this->config->get('security.uploads.image_quality', 90));
                $image->save($tmpPath, (int) $this->config->get('security.uploads.image_quality', 90));
            } else {
                $stream = fopen($file->getRealPath(), 'rb');
                $target = fopen($tmpPath, 'wb');

                if (! $stream || ! $target) {
                    throw new UnsafeFileException('Unable to process uploaded file.');
                }

                stream_copy_to_stream($stream, $target);
                fclose($stream);
                fclose($target);
            }
        } catch (Throwable $e) {
            @unlink($tmpPath);

            throw new UnsafeFileException('Failed to sanitize uploaded file: '.$e->getMessage());
        }

        return $tmpPath;
    }

    /**
     * @param  array<string, mixed>  $target
     * @param  array<string, mixed>  $options
     */
    private function createOptimizedVariant(array $target, array $options = []): void
    {
        if (! $target['optimized_path'] || ! file_exists($target['absolute_path'])) {
            return;
        }

        $image = Image::make($target['absolute_path'])->orientate();
        $image->resize($options['optimized_width'], $options['optimized_height'] ?? null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $image->save($target['optimized_path'], (int) $this->config->get('security.uploads.image_quality', 90));
    }
}
