<?php

declare(strict_types=1);

namespace App\Http\Requests\Carriers;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCarrierRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }
}
