<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\ExportPolicyTravelToPdfAction;
use App\Enums\PolicyClass;
use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class PoliciesTravelPdfExportController extends Controller
{
    #[Authorize('view', 'policy')]
    public function __invoke(Policy $policy, ExportPolicyTravelToPdfAction $action): Response
    {
        abort_unless($policy->class === PolicyClass::Travel, 404);

        $policy->load(['client', 'carrier', 'agent', 'travelDetails']);

        return $action->handle($policy);
    }
}
