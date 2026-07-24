<?php

declare(strict_types=1);

namespace App\Http\Requests\Notes;

use Illuminate\Foundation\Http\FormRequest;

final class StoreNoteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.config('notes.max_length')],
            'pinned' => ['sometimes', 'boolean'],
        ];
    }
}
