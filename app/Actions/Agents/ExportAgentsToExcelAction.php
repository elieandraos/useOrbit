<?php

declare(strict_types=1);

namespace App\Actions\Agents;

use App\Exports\AgentsExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ExportAgentsToExcelAction
{
    /**
     * @param  array{search?: string|null, archived?: bool|string|null}  $filters
     *
     * @throws Exception
     * @throws WriterException
     */
    public function handle(array $filters, ?string $sortColumn, string $sortDirection): BinaryFileResponse
    {
        return Excel::download(
            new AgentsExport($filters, $sortColumn, $sortDirection),
            'agents.xlsx',
        );
    }
}
