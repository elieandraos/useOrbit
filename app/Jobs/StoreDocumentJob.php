<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class StoreDocumentJob implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(public readonly Document $document) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }
    }
}
