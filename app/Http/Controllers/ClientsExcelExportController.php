<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Clients\ExportClientsToExcelAction;
use App\Http\Requests\Clients\IndexClientRequest;
use App\Models\Client;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ClientsExcelExportController extends Controller
{
    #[Authorize('viewAny', Client::class)]
    public function __invoke(IndexClientRequest $request, ExportClientsToExcelAction $action): BinaryFileResponse
    {
        $filters = $request->validated();

        $sortColumn = $filters['sort'] ?? null;
        $sortDirection = $filters['direction'] ?? 'asc';

        return $action->handle($filters, $sortColumn, $sortDirection);
    }
}
