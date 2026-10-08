<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Organizations\UpdateTwoFactorRequirementAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\OrganizationUpdateRequest;
use App\Http\Resources\CountryResource;
use App\Http\Resources\CurrencyResource;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Organization;
use App\Models\User;
use DateTimeZone;
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

        $organization = $user->organization;

        return Inertia::render('settings/Organization', [
            'name' => $organization->name,
            'defaultCountryId' => $organization->default_country_id,
            'defaultCurrencyId' => $organization->default_currency_id,
            'timezone' => $organization->timezone,
            'twoFactorRequired' => $organization->two_factor_required,
            'countries' => CountryResource::collection(Country::query()->orderBy('name')->get()),
            'currencies' => CurrencyResource::collection(Currency::query()->orderBy('code')->get()),
            'timezones' => DateTimeZone::listIdentifiers(DateTimeZone::ALL),
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
