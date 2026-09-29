<?php

declare(strict_types=1);

namespace App\Support\Policies;

use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Http\Resources\AgentResource;
use App\Http\Resources\CarrierResource;
use App\Http\Resources\ClientResource;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

final class PolicyFormOptions
{
    /**
     * The select-option props shared by every policy create and edit page.
     *
     * @return array{
     *     clients: AnonymousResourceCollection,
     *     carriers: AnonymousResourceCollection,
     *     agents: AnonymousResourceCollection,
     *     types: Collection<int, array{label: string, value: string}>,
     *     statuses: Collection<int, array{label: string, value: string}>,
     *     sources: Collection<int, array{label: string, value: string}>,
     * }
     */
    public function shared(): array
    {
        return [
            'clients' => ClientResource::collection(Client::query()->orderBy('id')->get()),
            'carriers' => CarrierResource::collection(Carrier::query()->orderBy('name')->get()),
            'agents' => AgentResource::collection(Agent::query()->orderBy('id')->get()),
            'types' => collect(PolicyType::all()),
            'statuses' => collect(PolicyStatus::all()),
            'sources' => collect(PolicySource::all()),
        ];
    }
}
