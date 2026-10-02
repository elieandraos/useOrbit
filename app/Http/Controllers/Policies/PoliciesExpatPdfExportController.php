<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\ExportPolicyExpatToPdfAction;
use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class PoliciesExpatPdfExportController extends Controller
{
    #[Authorize('view', 'policy')]
    public function __invoke(Policy $policy, ExportPolicyExpatToPdfAction $action): Response
    {
        $policy->load(['client', 'carrier', 'agent', 'currency', 'expatDetails.country']);

        return $action->handle($policy);
    }
}
