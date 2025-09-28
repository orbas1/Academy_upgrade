<?php

namespace App\Exceptions\Security;

use App\Models\UploadScan;
use RuntimeException;

class InfectedUploadException extends RuntimeException
{
    public function __construct(private readonly UploadScan $scan, string $message = 'Uploaded file failed antivirus scanning.')
    {
        parent::__construct($message);
    }

    public function scan(): UploadScan
    {
        return $this->scan;
    }
}
