<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Exports\PoliciesExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ExportPoliciesToExcelAction
{
    /**
     * @param  array{search?: string|null, status?: string|null, type?: string|null, class?: array<int, string>|null, carrier_id?: int|string|null, source?: string|null, effective_from?: string|null, effective_to?: string|null, amount_min?: int|string|null, amount_max?: int|string|null}  $filters
     *
     * @throws Exception
     * @throws WriterException
     */
    public function handle(array $filters, ?string $sortColumn, string $sortDirection): BinaryFileResponse
    {
        return Excel::download(
            new PoliciesExport($filters, $sortColumn, $sortDirection),
            'policies.xlsx',
        );
    }
}
