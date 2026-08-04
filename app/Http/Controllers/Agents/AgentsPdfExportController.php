<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agents;

use App\Actions\Agents\ExportAgentToPdfAction;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class AgentsPdfExportController extends Controller
{
    #[Authorize('view', 'agent')]
    public function __invoke(Agent $agent, ExportAgentToPdfAction $action): Response
    {
        $agent->load(['state', 'country']);

        return $action->handle($agent);
    }
}
