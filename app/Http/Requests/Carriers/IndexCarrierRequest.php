<?php

declare(strict_types=1);

namespace App\Http\Requests\Carriers;

use Illuminate\Foundation\Http\FormRequest;

final class IndexCarrierRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'archived' => ['boolean'],
            'sort' => ['nullable', 'in:name'],
            'direction' => ['in:asc,desc'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'archived' => $this->boolean('archived'),
            'direction' => $this->input('direction') ?? 'asc',
        ]);
    }
}
