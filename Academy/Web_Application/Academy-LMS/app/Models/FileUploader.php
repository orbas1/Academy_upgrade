<?php

namespace App\Models;

use App\Exceptions\Security\InfectedUploadException;
use App\Exceptions\Security\ScanFailedException;
use App\Exceptions\Security\UnsafeFileException;
use App\Services\Security\UploadSecurityService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class FileUploader extends Model
{
    use HasFactory;

    public static function upload($uploadedFile, $uploadTo, $width = null, $height = null, $optimizedWidth = 250, $optimizedHeight = null)
    {
        if (! $uploadedFile instanceof UploadedFile) {
            return null;
        }

        $service = app(UploadSecurityService::class);

        try {
            return $service->secureLegacyUpload($uploadedFile, $uploadTo, [
                'resize_width' => $width,
                'resize_height' => $height,
                'optimized_width' => $optimizedWidth,
                'optimized_height' => $optimizedHeight,
            ]);
        } catch (UnsafeFileException|ScanFailedException|InfectedUploadException $exception) {
            Session::flash('error', $exception->getMessage());

            throw ValidationException::withMessages([
                'file' => [$exception->getMessage()],
            ]);
        }
    }
}
