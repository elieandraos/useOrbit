<?php

declare(strict_types=1);

namespace App\Actions\Carriers;

use App\Exports\CarriersExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ExportCarriersToExcelAction
{
    /** @param  array{search?: string|null, archived?: bool|string|null}  $filters */
    public function handle(array $filters, ?string $sortColumn, string $sortDirection): BinaryFileResponse
    {
        return Excel::download(
            new CarriersExport($filters, $sortColumn, $sortDirection),
            'carriers.xlsx',
        );
    }
}
