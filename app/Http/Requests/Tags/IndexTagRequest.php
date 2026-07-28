<?php

declare(strict_types=1);

namespace App\Http\Requests\Tags;

use App\Models\Contracts\Taggable;
use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexTagRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'taggable_type' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $modelClass = Relation::getMorphedModel($value);

                    if ($modelClass === null || ! is_a($modelClass, Taggable::class, true)) {
                        $fail('The selected taggable type is invalid.');
                    }
                },
            ],
            'owner_type' => [
                'nullable',
                'string',
                Rule::requiredIf(fn (): bool => $this->ownerColumn() !== null),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (Relation::getMorphedModel((string) $value) === null) {
                        $fail('The selected owner type is invalid.');
                    }
                },
            ],
        ];
    }

    private function ownerColumn(): ?string
    {
        $modelClass = Relation::getMorphedModel((string) $this->input('taggable_type'));

        if ($modelClass === null || ! is_a($modelClass, Taggable::class, true)) {
            return null;
        }

        /** @var class-string<Taggable> $modelClass */
        return $modelClass::ownerColumn();
    }
}
