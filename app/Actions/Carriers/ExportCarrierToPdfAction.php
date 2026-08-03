<?php

declare(strict_types=1);

namespace App\Actions\Carriers;

use App\Models\Carrier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class ExportCarrierToPdfAction
{
    public function handle(Carrier $carrier): Response
    {
        return Pdf::loadView('exports.carrier-profile', ['carrier' => $carrier])
            ->download("$carrier->slug.pdf");
    }
}
