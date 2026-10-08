<?php

declare(strict_types=1);

namespace App\Support\Policies;

use App\Enums\AgentStatus;
use App\Enums\CarrierStatus;
use App\Enums\PolicySource;
use App\Enums\PolicyType;
use App\Http\Resources\CurrencyResource;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\CarrierBranch;
use App\Models\Currency;
use App\Models\Organization;
use App\Models\Policy;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

final readonly class PolicyFormOptions
{
    public function __construct(private OrganizationContext $organizationContext) {}

    /**
     * The select-option props shared by every policy create and edit page.
     *
     * Each party option carries only its id and the displayed name its select renders, and each carrier
     * also lists its branches by id and label for the issuing branch select. Only active
     * parties are offered, except that an edited policy keeps its currently assigned carrier and agent
     * among the options even after they have been archived. Clients aren't listed: the client field
     * searches them instead. A new policy also gets the organization's default currency to pre-select;
     * an edited policy keeps its own.
     *
     * @return array{
     *     carriers: Collection<int, array{id: int, name: string, branches: Collection<int, array{id: int, label: string}>}>,
     *     agents: Collection<int, array{id: int, full_name: string}>,
     *     types: Collection<int, array{label: string, value: string}>,
     *     sources: Collection<int, array{label: string, value: string}>,
     *     currencies: AnonymousResourceCollection,
     *     defaultCurrencyId?: int|null,
     * }
     */
    public function shared(?Policy $policy = null): array
    {
        return [
            'carriers' => $this->carriers($policy?->carrier_id)->map(fn (Carrier $carrier): array => [
                'id' => $carrier->id,
                'name' => $carrier->name,
                'branches' => $carrier->branches->map(fn (CarrierBranch $branch): array => ['id' => $branch->id, 'label' => $branch->label]),
            ]),
            'agents' => $this->agents($policy?->agent_id)->map(fn (Agent $agent): array => ['id' => $agent->id, 'full_name' => $agent->full_name]),
            'types' => collect(PolicyType::all()),
            'sources' => collect(PolicySource::all()),
            'currencies' => CurrencyResource::collection(Currency::query()->orderBy('code')->get()),
            ...($policy === null ? ['defaultCurrencyId' => $this->defaultCurrencyId()] : []),
        ];
    }

    /**
     * The current organization's default currency, read fresh so a changed default applies to the next new policy.
     */
    private function defaultCurrencyId(): ?int
    {
        /** @var int|null $currencyId */
        $currencyId = Organization::query()->whereKey($this->organizationContext->id())->value('default_currency_id');

        return $currencyId;
    }

    /**
     * Active carriers, plus the kept one, ordered by name, each with its branches ordered by label.
     *
     * @return EloquentCollection<int, Carrier>
     */
    private function carriers(?int $keptCarrierId): EloquentCollection
    {
        return Carrier::query()
            ->with(['branches' => fn (HasMany $query): HasMany => $query->select(['id', 'carrier_id', 'city', 'street'])->orderBy('city')->orderBy('street')->orderBy('id')])
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
