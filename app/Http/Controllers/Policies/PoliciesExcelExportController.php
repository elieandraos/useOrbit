<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\ExportPoliciesToExcelAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Policies\IndexPolicyRequest;
use App\Models\Policy;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use PhpOffice\PhpSpreadsheet\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PoliciesExcelExportController extends Controller
{
    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    #[Authorize('viewAny', Policy::class)]
    public function __invoke(IndexPolicyRequest $request, ExportPoliciesToExcelAction $action): BinaryFileResponse
    {
        $filters = $request->validated();

        $sortColumn = $filters['sort'] ?? null;
        $sortDirection = $filters['direction'] ?? 'asc';

        return $action->handle($filters, $sortColumn, $sortDirection);
    }
}
