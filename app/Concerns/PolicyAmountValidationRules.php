<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait PolicyAmountValidationRules
{
    /**
     * Get the validation rules every policy amount shares: a non-negative number with at most two
     * decimals that fits its `decimal(15, 2)` column, whose largest value is 9,999,999,999,999.99.
     *
     * @return array<int, ValidationRule|array|string>
     */
    protected function policyAmountRules(): array
    {
        return ['numeric', 'decimal:0,2', 'min:0', 'max:9999999999999.99'];
    }
}
