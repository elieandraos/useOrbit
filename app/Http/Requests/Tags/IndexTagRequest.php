<?php

declare(strict_types=1);

namespace App\Http\Requests\Tags;

use App\Models\Contracts\Documentable;
use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexTagRequest extends FormRequest
{
    public function rules(): array
    {
        $ownerTable = $this->ownerTable();

        return [
            'owner_type' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $modelClass = Relation::getMorphedModel($value);

                    if ($modelClass === null || ! is_a($modelClass, Documentable::class, true)) {
                        $fail('The selected owner type is invalid.');
                    }
                },
            ],
            'owner_id' => [
                'required',
                'integer',
                Rule::when(
                    $ownerTable !== null,
                    [Rule::exists($ownerTable, 'id')->where('organization_id', $this->user()?->current_organization_id)],
                ),
            ],
        ];
    }

    private function ownerTable(): ?string
    {
        $modelClass = Relation::getMorphedModel((string) $this->input('owner_type'));

        if ($modelClass === null) {
            return null;
        }

        return (new $modelClass)->getTable();
    }
}
