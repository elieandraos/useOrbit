<?php

declare(strict_types=1);

namespace App\Http\Controllers\Carriers;

use App\Actions\Carriers\UnarchiveCarrierAction;
use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class CarriersUnarchiveController extends Controller
{
    /**
     * @throws \Throwable
     */
    #[Authorize('unarchive', 'carrier')]
    public function __invoke(Request $request, Carrier $carrier, UnarchiveCarrierAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $carrier);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Carrier unarchived.')]);

        return to_route('carriers.index');
    }
}
