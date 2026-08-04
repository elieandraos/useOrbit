<?php

declare(strict_types=1);

namespace App\Actions\Agents;

use App\Models\Agent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class ExportAgentToPdfAction
{
    public function handle(Agent $agent): Response
    {
        return Pdf::loadView('exports.agent-profile', ['agent' => $agent])
            ->download("$agent->slug.pdf");
    }
}
