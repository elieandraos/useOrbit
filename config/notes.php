<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Max length
    |--------------------------------------------------------------------------
    |
    | Maximum number of characters allowed in a note's body. Kept short and
    | skimmable (roughly 2-3 lines in the composer) rather than open-ended.
    |
    */

    'max_length' => env('NOTES_MAX_LENGTH', 240),

];
