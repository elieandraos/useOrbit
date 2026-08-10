<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Exports\ClientsExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ExportClientsToExcelAction
{
    /**
     * @param  array{search?: string|null, gender?: string|null, enrolled_from?: string|null, enrolled_to?: string|null, age_min?: int|string|null, age_max?: int|string|null, archived?: bool|string|null}  $filters
     *
     * @throws Exception
     * @throws WriterException
     */
    public function handle(array $filters, ?string $sortColumn, string $sortDirection): BinaryFileResponse
    {
        return Excel::download(
            new ClientsExport($filters, $sortColumn, $sortDirection),
            'clients.xlsx',
        );
    }
}
