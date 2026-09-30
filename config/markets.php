<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Countries
    |--------------------------------------------------------------------------
    |
    | The operating markets policies may be written in, as uppercase ISO2 codes
    | matching `countries.iso2` (e.g. "LB,AE,SA"). Required for Policies: an
    | empty or missing value offers and accepts no country, never all of them.
    | A temporary source until markets are provisioned per organization.
    |
    */

    'countries' => array_values(array_filter(array_map(
        fn (string $code): string => strtoupper(trim($code)),
        explode(',', (string) env('MARKET_COUNTRIES', '')),
    ))),

];
