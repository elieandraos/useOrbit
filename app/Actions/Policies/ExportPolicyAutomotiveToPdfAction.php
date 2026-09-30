<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class ExportPolicyAutomotiveToPdfAction
{
    public function handle(Policy $policy): Response
    {
        return Pdf::loadView('exports.policy-automotive-profile', ['policy' => $policy])
            ->download("$policy->slug.pdf");
    }
}
