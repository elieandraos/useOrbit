<?php

declare(strict_types=1);

namespace App\Http\Controllers\Carriers;

use App\Actions\Carriers\CreateCarrierAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Carriers\StoreCarrierRequest;
use App\Http\Resources\CarrierResource;
use App\Models\Carrier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class CarriersController extends Controller
{
    #[Authorize('viewAny', Carrier::class)]
    public function index(): Response
    {
        $carriers = Carrier::query()
            ->with('hqBranch')
            ->latest('onboarded_date')
            ->paginate(7)
            ->withQueryString();

        return inertia('Carriers/Index', [
            'carriers' => CarrierResource::collection($carriers),
        ]);
    }

    #[Authorize('create', Carrier::class)]
    public function create(): Response
    {
        return inertia('Carriers/Create');
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('create', Carrier::class)]
    public function store(StoreCarrierRequest $request, CreateCarrierAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $carrier = $action->handle($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Carrier created.')]);

        return to_route('carriers.show', $carrier);
    }
}