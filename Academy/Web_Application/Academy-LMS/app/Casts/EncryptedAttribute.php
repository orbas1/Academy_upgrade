<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptedAttribute implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }

        $decoded = json_decode($decrypted, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $decrypted;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            $value = json_encode(Arr::toArray($value), JSON_UNESCAPED_UNICODE);
        }

        return Crypt::encryptString((string) $value);
    }
}
