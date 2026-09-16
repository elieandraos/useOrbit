<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\ExportPolicyFireToPdfAction;
use App\Enums\PolicyClass;
use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class PoliciesFirePdfExportController extends Controller
{
    #[Authorize('view', 'policy')]
    public function __invoke(Policy $policy, ExportPolicyFireToPdfAction $action): Response
    {
        abort_unless($policy->class === PolicyClass::Fire, 404);

        $policy->load(['client', 'carrier', 'agent', 'fireDetails.state', 'fireDetails.country']);

        return $action->handle($policy);
    }
}
