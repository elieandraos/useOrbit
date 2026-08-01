<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\UpdateClientAction;
use App\Enums\ClientType;
use App\Enums\EmergencyContactRelationship;
use App\Enums\Gender;
use App\Enums\LeadSource;
use App\Filters\ClientFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\IndexClientRequest;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\CountryResource;
use App\Models\Client;
use App\Models\Country;
use App\Models\User;
use App\Sorts\ClientSort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

final class ClientsController extends Controller
{
    #[Authorize('viewAny', Client::class)]
    public function index(IndexClientRequest $request): Response
    {
        $sortColumn = $request->validated('sort');

        /** @noinspection PhpUndefinedMethodInspection */
        $clients = Client::query()
            ->filter(new ClientFilter($request->validated()))
            ->sort(new ClientSort($sortColumn, $request->validated('direction')))
            ->paginate(7)
            ->withQueryString();

        return inertia('Clients/Index', [
            'clients' => ClientResource::collection($clients),
            'genders' => collect(Gender::all()),
            'clientTypes' => collect(ClientType::all()),
            'sort' => [
                'column' => $sortColumn ?? 'enrollment_date',
                'direction' => $request->validated('direction'),
            ],
            'filters' => [
                'search' => $request->validated('search'),
                'client_type' => $request->validated('client_type'),
                'gender' => $request->validated('gender'),
                'enrolled_from' => $request->validated('enrolled_from'),
                'enrolled_to' => $request->validated('enrolled_to'),
                'age_min' => $request->validated('age_min'),
                'age_max' => $request->validated('age_max'),
                'archived' => $request->validated('archived'),
            ],
        ]);
    }

    #[Authorize('create', Client::class)]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return inertia('Clients/Create', [
            'countries' => CountryResource::collection(Country::query()->orderBy('name')->get()),
            'genders' => collect(Gender::all()),
            'leadSources' => collect(LeadSource::all()),
            'emergencyContactRelationships' => collect(EmergencyContactRelationship::all()),
            'clientTypes' => collect(ClientType::all()),
            'defaultCountryId' => $user->country_id,
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
        $client->load(['country', 'state']);

        return inertia('Clients/Show', [
            'client' => ClientResource::make($client),
        ]);
    }

    #[Authorize('update', 'client')]
    public function edit(Client $client): Response
    {
        $client->load(['updatedBy', 'country', 'state']);

        return inertia('Clients/Edit', [
            'client' => ClientResource::make($client),
            'countries' => CountryResource::collection(Country::query()->orderBy('name')->get()),
            'genders' => collect(Gender::all()),
            'leadSources' => collect(LeadSource::all()),
            'emergencyContactRelationships' => collect(EmergencyContactRelationship::all()),
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
