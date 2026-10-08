<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Organizations\UpdateOrganizationDetailsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\OrganizationDetailsUpdateRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class OrganizationDetailsController extends Controller
{
    /**
     * Update the organization's name, default country, default currency and timezone.
     */
    #[Authorize('update', Organization::class)]
    public function __invoke(OrganizationDetailsUpdateRequest $request, UpdateOrganizationDetailsAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->handle($user->organization, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organization settings updated.')]);

        return back();
    }
}
