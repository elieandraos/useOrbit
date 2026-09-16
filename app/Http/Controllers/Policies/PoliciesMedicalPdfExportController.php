<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Actions\Policies\ExportPolicyMedicalToPdfAction;
use App\Enums\PolicyClass;
use App\Enums\PolicyType;
use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class PoliciesMedicalPdfExportController extends Controller
{
    #[Authorize('view', 'policy')]
    public function __invoke(Policy $policy, ExportPolicyMedicalToPdfAction $action): Response
    {
        abort_unless($policy->class === PolicyClass::Medical, 404);

        $policy->load(['client', 'carrier', 'agent', 'medicalDetails']);

        if ($policy->type === PolicyType::Group) {
            $policy->load('insureds');
        }

        return $action->handle($policy);
    }
}
