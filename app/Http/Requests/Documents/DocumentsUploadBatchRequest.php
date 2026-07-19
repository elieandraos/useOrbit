<?php

declare(strict_types=1);

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

final class DocumentsUploadBatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'document_ids' => ['required', 'array', 'max:'.config('documents.max_files_per_batch')],
            'document_ids.*' => ['integer'],
        ];
    }
}
