<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UploadScan extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CLEAN = 'clean';
    public const STATUS_INFECTED = 'infected';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'path',
        'absolute_path',
        'mime_type',
        'status',
        'details',
        'quarantine_path',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function markClean(?string $details = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_CLEAN,
            'details' => $details,
            'scanned_at' => now(),
        ])->save();
    }

    public function markSkipped(?string $details = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_SKIPPED,
            'details' => $details,
            'scanned_at' => now(),
        ])->save();
    }

    public function markFailed(?string $details = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'details' => $details,
            'scanned_at' => now(),
        ])->save();
    }

    public function markInfected(?string $details = null, ?string $quarantinePath = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_INFECTED,
            'details' => $details,
            'quarantine_path' => $quarantinePath,
            'scanned_at' => now(),
        ])->save();
    }

    public function moveToQuarantine(string $quarantineRoot): ?string
    {
        if (! $this->absolute_path || ! file_exists($this->absolute_path)) {
            return null;
        }

        $directory = rtrim($quarantineRoot, DIRECTORY_SEPARATOR);
        if (! is_dir($directory)) {
            File::makeDirectory($directory, 0775, true);
        }

        $fileName = Str::uuid().'-'.basename($this->absolute_path);
        $target = $directory.DIRECTORY_SEPARATOR.$fileName;

        if (@rename($this->absolute_path, $target)) {
            $this->forceFill(['absolute_path' => $target])->save();

            return $target;
        }

        return null;
    }

    public function scanIsClean(): bool
    {
        return $this->status === self::STATUS_CLEAN || $this->status === self::STATUS_SKIPPED;
    }

    public function scanIsInfected(): bool
    {
        return $this->status === self::STATUS_INFECTED;
    }

    public function scanIsFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
