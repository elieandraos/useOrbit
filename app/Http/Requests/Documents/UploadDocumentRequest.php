<?php

declare(strict_types=1);

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

final class UploadDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.intdiv(config('documents.max_size'), 1024),
                'mimes:'.implode(',', config('documents.allowed_mimes')),
            ],
        ];
    }
}
