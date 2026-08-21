<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Organizations\UpdateTwoFactorRequirementAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\OrganizationUpdateRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizationController extends Controller
{
    /**
     * Show the organization settings page.
     */
    #[Authorize('update', Organization::class)]
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('settings/Organization', [
            'twoFactorRequired' => $user->organization->two_factor_required,
        ]);
    }

    /**
     * Update the organization's two-factor authentication requirement.
     */
    #[Authorize('update', Organization::class)]
    public function update(OrganizationUpdateRequest $request, UpdateTwoFactorRequirementAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->handle($user->organization, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organization settings updated.')]);

        return back();
    }
}
