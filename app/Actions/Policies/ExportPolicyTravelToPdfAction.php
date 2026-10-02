<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class ExportPolicyTravelToPdfAction
{
    public function handle(Policy $policy): Response
    {
        return Pdf::loadView('exports.policy-travel-profile', ['policy' => $policy, 'organization' => $policy->organization])
            ->download("$policy->slug.pdf");
    }
}
