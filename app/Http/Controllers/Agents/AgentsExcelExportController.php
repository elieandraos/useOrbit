<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agents;

use App\Actions\Agents\ExportAgentsToExcelAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agents\IndexAgentRequest;
use App\Models\Agent;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class AgentsExcelExportController extends Controller
{
    #[Authorize('viewAny', Agent::class)]
    public function __invoke(IndexAgentRequest $request, ExportAgentsToExcelAction $action): BinaryFileResponse
    {
        $filters = $request->validated();

        $sortColumn = $filters['sort'] ?? null;
        $sortDirection = $filters['direction'] ?? 'asc';

        return $action->handle($filters, $sortColumn, $sortDirection);
    }
}
