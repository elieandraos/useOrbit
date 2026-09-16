<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\ExportPolicyLifeToPdfAction;
use App\Enums\PolicyClass;
use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class PoliciesLifePdfExportController extends Controller
{
    #[Authorize('view', 'policy')]
    public function __invoke(Policy $policy, ExportPolicyLifeToPdfAction $action): Response
    {
        abort_unless($policy->class === PolicyClass::Life, 404);

        $policy->load(['client', 'carrier', 'agent', 'lifeDetails']);

        return $action->handle($policy);
    }
}
