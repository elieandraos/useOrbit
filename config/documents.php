<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk documents are stored on once finalized.
    |
    */

    'disk' => env('DOCUMENTS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Max size
    |--------------------------------------------------------------------------
    |
    | Maximum allowed size per uploaded file, in bytes.
    |
    */

    'max_size' => 15 * 1024 * 1024,

    /*
    |--------------------------------------------------------------------------
    | Allowed mimes
    |--------------------------------------------------------------------------
    |
    | File extensions accepted by the upload validation (checked via content
    | sniffing, not the client-supplied extension).
    |
    */

    'allowed_mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'],

    /*
    |--------------------------------------------------------------------------
    | Prune after hours
    |--------------------------------------------------------------------------
    |
    | Staged documents still pending after this many hours are considered
    | stale and removed by the `model:prune` command via Document::prunable().
    |
    */

    'prune_after_hours' => 1,

    /*
    |--------------------------------------------------------------------------
    | Max files per batch
    |--------------------------------------------------------------------------
    |
    | Maximum number of files accepted in a single upload batch.
    |
    */

    'max_files_per_batch' => 10,

];
