<?php

declare(strict_types=1);

namespace App\Support\Policies;

use App\Enums\AgentStatus;
use App\Enums\CarrierStatus;
use App\Enums\ClientStatus;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Http\Resources\AgentResource;
use App\Http\Resources\CarrierResource;
use App\Http\Resources\ClientResource;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Policy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

final class PolicyFormOptions
{
    /**
     * The select-option props shared by every policy create and edit page.
     *
     * Only active parties are offered, except that an edited policy keeps its currently assigned
     * client, carrier and agent among the options even after they have been archived.
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
    public function shared(?Policy $policy = null): array
    {
        return [
            'clients' => ClientResource::collection($this->clients($policy?->client_id)),
            'carriers' => CarrierResource::collection($this->carriers($policy?->carrier_id)),
            'agents' => AgentResource::collection($this->agents($policy?->agent_id)),
            'types' => collect(PolicyType::all()),
            'statuses' => collect(PolicyStatus::all()),
            'sources' => collect(PolicySource::all()),
        ];
    }

    /**
     * Active clients, plus the kept one, ordered by their displayed name: the company name for a company, otherwise first then last name.
     *
     * @return EloquentCollection<int, Client>
     */
    private function clients(?int $keptClientId): EloquentCollection
    {
        return Client::query()
            ->where(fn (Builder $query): Builder => $this->activeOrKept($query, ClientStatus::Active->value, $keptClientId))
            ->orderByRaw("CASE WHEN client_type = 'company' THEN company_name ELSE first_name END")
            ->orderByRaw("CASE WHEN client_type = 'company' THEN company_name ELSE last_name END")
            ->orderBy('id')
            ->get();
    }

    /**
     * Active carriers, plus the kept one, ordered by name.
     *
     * @return EloquentCollection<int, Carrier>
     */
    private function carriers(?int $keptCarrierId): EloquentCollection
    {
        return Carrier::query()
            ->where(fn (Builder $query): Builder => $this->activeOrKept($query, CarrierStatus::Active->value, $keptCarrierId))
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * Active agents, plus the kept one, ordered by their displayed name: first then last name.
     *
     * @return EloquentCollection<int, Agent>
     */
    private function agents(?int $keptAgentId): EloquentCollection
    {
        return Agent::query()
            ->where(fn (Builder $query): Builder => $this->activeOrKept($query, AgentStatus::Active->value, $keptAgentId))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('id')
            ->get();
    }

    /**
     * Constrain a party query to active rows, or the kept row when one is given.
     */
    private function activeOrKept(Builder $query, string $activeStatus, ?int $keptId): Builder
    {
        return $query
            ->where('status', $activeStatus)
            ->when($keptId !== null, fn (Builder $query): Builder => $query->orWhere('id', $keptId));
    }
}
