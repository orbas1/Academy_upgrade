<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class InternalRedirect implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            $fail(__('validation.string', ['attribute' => $attribute]));
            return;
        }

        if (!Str::startsWith($value, '/')) {
            $fail(__('validation.url', ['attribute' => $attribute]));
            return;
        }

        if (str_starts_with($value, '//')) {
            $fail(__('validation.url', ['attribute' => $attribute]));
            return;
        }

        $segments = parse_url($value);
        if ($segments === false) {
            $fail(__('validation.url', ['attribute' => $attribute]));
            return;
        }

        if (!empty($segments['scheme']) || !empty($segments['host'])) {
            $fail(__('validation.url', ['attribute' => $attribute]));
        }
    }
}
