<?php

declare(strict_types=1);

namespace App\Http\Controllers\Carriers;

use App\Actions\Carriers\ExportCarrierToPdfAction;
use App\Http\Controllers\Controller;
use App\Models\Carrier;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class CarriersPdfExportController extends Controller
{
    #[Authorize('view', 'carrier')]
    public function __invoke(Carrier $carrier, ExportCarrierToPdfAction $action): Response
    {
        $carrier->load(['branches.state', 'branches.country']);

        return $action->handle($carrier);
    }
}
