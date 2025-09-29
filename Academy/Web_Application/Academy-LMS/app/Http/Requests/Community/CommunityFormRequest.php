<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;

abstract class CommunityFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
