<?php

declare(strict_types=1);

namespace App\Http\Requests\Tags;

use Illuminate\Foundation\Http\FormRequest;

final class IndexTagRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'documentable_type' => ['required', 'string', 'in:clients'],
        ];
    }
}
