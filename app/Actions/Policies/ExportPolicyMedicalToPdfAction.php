<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use App\Support\Policies\PolicyDisplayStatusResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class ExportPolicyMedicalToPdfAction
{
    public function __construct(private readonly PolicyDisplayStatusResolver $displayStatusResolver) {}

    public function handle(Policy $policy): Response
    {
        return Pdf::loadView('exports.policy-medical-profile', [
            'policy' => $policy,
            'organization' => $policy->organization,
            'displayStatus' => $this->displayStatusResolver->for($policy),
        ])
            ->download("$policy->slug.pdf");
    }
}
