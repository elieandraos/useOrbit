<?php

declare(strict_types=1);

namespace App\Http\Controllers\Carriers;

use App\Actions\Carriers\ExportCarriersToExcelAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Carriers\IndexCarrierRequest;
use App\Models\Carrier;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CarriersExcelExportController extends Controller
{
    #[Authorize('viewAny', Carrier::class)]
    public function __invoke(IndexCarrierRequest $request, ExportCarriersToExcelAction $action): BinaryFileResponse
    {
        $filters = $request->validated();

        $sortColumn = $filters['sort'] ?? null;
        $sortDirection = $filters['direction'] ?? 'asc';

        return $action->handle($filters, $sortColumn, $sortDirection);
    }
}
