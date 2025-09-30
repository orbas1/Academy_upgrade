<?php

declare(strict_types=1);

namespace App\Http\Requests\Observability;

use Illuminate\Foundation\Http\FormRequest;

class StoreMobileMetricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'string', 'max:64'],
            'environment' => ['nullable', 'string', 'max:64'],
            'metrics' => ['required', 'array', 'min:1', 'max:100'],
            'metrics.*.name' => ['required', 'string', 'in:http_request'],
            'metrics.*.timestamp' => ['required', 'date'],
            'metrics.*.method' => ['required', 'string', 'max:10'],
            'metrics.*.route' => ['nullable', 'string', 'max:120'],
            'metrics.*.path' => ['nullable', 'string', 'max:255'],
            'metrics.*.duration_ms' => ['required', 'numeric', 'min:0'],
            'metrics.*.status_code' => ['nullable', 'integer', 'min:0'],
            'metrics.*.request_id' => ['nullable', 'string', 'max:64'],
            'metrics.*.network_type' => ['nullable', 'string', 'max:32'],
            'metrics.*.error' => ['nullable', 'string', 'max:255'],
        ];
    }
}
