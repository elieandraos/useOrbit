<?php

declare(strict_types=1);

namespace App\Http\Controllers\Carriers;

use App\Actions\Carriers\CreateCarrierBranchAction;
use App\Actions\Carriers\DeleteCarrierBranchAction;
use App\Actions\Carriers\UpdateCarrierBranchAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Carriers\StoreCarrierBranchRequest;
use App\Http\Requests\Carriers\UpdateCarrierBranchRequest;
use App\Models\Carrier;
use App\Models\CarrierBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class CarriersBranchController extends Controller
{
    #[Authorize('create', [CarrierBranch::class, 'carrier'])]
    public function store(StoreCarrierBranchRequest $request, Carrier $carrier, CreateCarrierBranchAction $action): RedirectResponse
    {
        $action->handle($carrier, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branch added.')]);

        return to_route('carriers.show', $carrier);
    }

    #[Authorize('update', 'branch')]
    public function update(UpdateCarrierBranchRequest $request, CarrierBranch $branch, UpdateCarrierBranchAction $action): RedirectResponse
    {
        $action->handle($branch, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branch updated.')]);

        return to_route('carriers.show', $branch->carrier);
    }

    #[Authorize('delete', 'branch')]
    public function destroy(CarrierBranch $branch, DeleteCarrierBranchAction $action): RedirectResponse
    {
        $carrier = $branch->carrier;

        $action->handle($branch);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branch deleted.')]);

        return to_route('carriers.show', $carrier);
    }
}
