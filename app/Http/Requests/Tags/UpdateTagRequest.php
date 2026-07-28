<?php

declare(strict_types=1);

namespace App\Http\Requests\Tags;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTagRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Tag $tag */
        $tag = $this->route('tag');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tags')
                    ->where('organization_id', $tag->organization_id)
                    ->ignore($tag->id),
            ],
        ];
    }
}
