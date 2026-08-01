<?php

declare(strict_types=1);

namespace App\Http\Controllers\Carriers;

use App\Actions\Carriers\ArchiveCarrierAction;
use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class CarriersArchiveController extends Controller
{
    /**
     * @throws \Throwable
     */
    #[Authorize('archive', 'carrier')]
    public function __invoke(Request $request, Carrier $carrier, ArchiveCarrierAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $carrier);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Carrier archived.')]);

        return to_route('carriers.index');
    }
}
