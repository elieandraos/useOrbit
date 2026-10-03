<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\ExportPolicyAutomotiveToPdfAction;
use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class PoliciesAutomotivePdfExportController extends Controller
{
    #[Authorize('view', 'policy')]
    public function __invoke(Policy $policy, ExportPolicyAutomotiveToPdfAction $action): Response
    {
        $policy->load(['client', 'carrier', 'carrierBranch', 'agent', 'currency', 'automotiveDetails']);

        return $action->handle($policy);
    }
}
