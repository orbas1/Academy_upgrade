<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptedAttribute implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $payload = $this->extractCipherPayload($value);

        if ($payload === null) {
            return $value;
        }

        try {
            $decrypted = Crypt::decryptString($payload);
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
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif (is_object($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
            $value = $encoded === false ? (string) $value : $encoded;
        }

        $ciphertext = Crypt::encryptString((string) $value);

        return json_encode([
            'v' => 1,
            'ciphertext' => $ciphertext,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function extractCipherPayload(mixed $value): ?string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_string($decoded)) {
                    return $decoded;
                }

                if (is_array($decoded) && isset($decoded['ciphertext']) && is_string($decoded['ciphertext'])) {
                    return $decoded['ciphertext'];
                }
            }

            return trim($value, "\"\'");
        }

        if (is_array($value) && isset($value['ciphertext']) && is_string($value['ciphertext'])) {
            return $value['ciphertext'];
        }

        return null;
    }
}
