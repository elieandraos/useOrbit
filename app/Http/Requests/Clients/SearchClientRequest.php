<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use Illuminate\Foundation\Http\FormRequest;

final class SearchClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }
}
