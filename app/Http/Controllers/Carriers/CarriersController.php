<?php

declare(strict_types=1);

namespace App\Http\Controllers\Carriers;

use App\Actions\Carriers\CreateCarrierAction;
use App\Actions\Carriers\UpdateCarrierAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Carriers\StoreCarrierRequest;
use App\Http\Requests\Carriers\UpdateCarrierRequest;
use App\Http\Resources\CarrierResource;
use App\Http\Resources\CountryResource;
use App\Models\Carrier;
use App\Models\Country;
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
            ->orderBy('name')
            ->paginate(7)
            ->withQueryString();

        return inertia('Carriers/Index', [
            'carriers' => CarrierResource::collection($carriers),
        ]);
    }

    #[Authorize('create', Carrier::class)]
    public function create(): Response
    {
        return inertia('Carriers/Create', [
            'countries' => CountryResource::collection(Country::query()->orderBy('name')->get()),
        ]);
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

    #[Authorize('view', 'carrier')]
    public function show(Carrier $carrier): Response
    {
        $carrier->load(['hqBranch.state', 'hqBranch.country']);

        return inertia('Carriers/Show', [
            'carrier' => CarrierResource::make($carrier),
        ]);
    }

    #[Authorize('update', 'carrier')]
    public function edit(Carrier $carrier): Response
    {
        $carrier->load(['hqBranch.state', 'hqBranch.country', 'updatedBy']);

        return inertia('Carriers/Edit', [
            'carrier' => CarrierResource::make($carrier),
            'countries' => CountryResource::collection(Country::query()->orderBy('name')->get()),
        ]);
    }

    /**
     * @throws \Throwable
     */
    #[Authorize('update', 'carrier')]
    public function update(UpdateCarrierRequest $request, Carrier $carrier, UpdateCarrierAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $carrier = $action->handle($user, $carrier, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Carrier updated.')]);

        return to_route('carriers.show', $carrier);
    }

    #[Authorize('delete', 'carrier')]
    public function destroy(Carrier $carrier): RedirectResponse
    {
        $carrier->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Carrier deleted.')]);

        return to_route('carriers.index');
    }
}
