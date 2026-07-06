<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\UpdateClientAction;
use App\Enums\EmergencyContactRelationship;
use App\Enums\Gender;
use App\Enums\LeadSource;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\CountryResource;
use App\Models\Client;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class ClientsController extends Controller
{
    #[Authorize('viewAny', Client::class)]
    public function index(): Response
    {
        $clients = Client::query()->latest('enrollment_date')->paginate(7);

        return inertia('Clients/Index', [
            'clients' => ClientResource::collection($clients),
        ]);
    }

    #[Authorize('create', Client::class)]
    public function create(): Response
    {
        /** @var Country|null $defaultCountry */
        $defaultCountry = Country::query()->firstWhere('name', 'Lebanon');

        return inertia('Clients/Create', [
            'countries' => CountryResource::collection(Country::query()->orderBy('name')->get()),
            'genders' => collect(Gender::cases())->map(fn ($case) => ['label' => $case->label(), 'value' => $case->value]),
            'leadSources' => collect(LeadSource::cases())->map(fn ($case) => ['label' => $case->label(), 'value' => $case->value]),
            'emergencyContactRelationships' => collect(EmergencyContactRelationship::cases())->map(fn ($case) => ['label' => $case->label(), 'value' => $case->value]),
            'defaultCountryId' => $defaultCountry?->id,
        ]);
    }

    #[Authorize('create', Client::class)]
    public function store(StoreClientRequest $request, CreateClientAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $client = $action->handle($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client created.')]);

        return to_route('clients.show', $client);
    }

    #[Authorize('view', 'client')]
    public function show(Client $client): Response
    {
        $client->load('country');

        return inertia('Clients/Show', [
            'client' => ClientResource::make($client),
        ]);
    }

    #[Authorize('update', 'client')]
    public function edit(Client $client): Response
    {
        $client->load('updatedBy');

        return inertia('Clients/Edit', [
            'client' => ClientResource::make($client),
            'countries' => CountryResource::collection(Country::query()->orderBy('name')->get()),
            'genders' => collect(Gender::cases())->map(fn ($case) => ['label' => $case->label(), 'value' => $case->value]),
            'leadSources' => collect(LeadSource::cases())->map(fn ($case) => ['label' => $case->label(), 'value' => $case->value]),
            'emergencyContactRelationships' => collect(EmergencyContactRelationship::cases())->map(fn ($case) => ['label' => $case->label(), 'value' => $case->value]),
        ]);
    }

    #[Authorize('update', 'client')]
    public function update(UpdateClientRequest $request, Client $client, UpdateClientAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $client = $action->handle($user, $client, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client updated.')]);

        return to_route('clients.show', $client);
    }

    #[Authorize('delete', 'client')]
    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Client deleted.')]);

        return to_route('clients.index');
    }
}
