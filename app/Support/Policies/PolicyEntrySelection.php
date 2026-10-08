<?php

declare(strict_types=1);

namespace App\Support\Policies;

use App\Enums\AgentStatus;
use App\Enums\CarrierStatus;
use App\Enums\ClientStatus;
use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyType;
use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use BackedEnum;
use Illuminate\Http\Request;

/**
 * Resolves the choices made on the first step of a new policy — class, type, client, carrier, agent and lead
 * source — from the query string the steps carry between them.
 *
 * Only values that are still valid are kept: enum values must be one of the enum's cases, ids must be single
 * values, and parties must be active and belong to the current organization. Store-time validation stays the
 * authority for every one of them.
 */
final readonly class PolicyEntrySelection
{
    /**
     * The carried-over choices that are still valid, by the first step's field names.
     *
     * @return array{class: string|null, type: string|null, client_id: int|null, carrier_id: int|null, agent_id: int|null, source: string|null}
     */
    public function selected(Request $request): array
    {
        $resolved = $this->resolve($request);

        return [
            'class' => $this->enumValue($request, 'class', PolicyClass::class)?->value,
            'type' => $resolved['type']?->value,
            'client_id' => $resolved['client']?->id,
            'carrier_id' => $resolved['carrier']?->id,
            'agent_id' => $resolved['agent']?->id,
            'source' => $resolved['source']?->value,
        ];
    }

    /**
     * The choices a class's details step summarizes, with their displayed labels, or null when a required one
     * is missing or no longer valid.
     *
     * @return array{class: array{value: string, label: string}, type: array{value: string, label: string}, client: array{id: int, full_name: string}, carrier: array{id: int, name: string}, agent: array{id: int, full_name: string}|null, source: array{value: string, label: string}}|null
     */
    public function summary(Request $request, PolicyClass $policyClass): ?array
    {
        ['type' => $type, 'client' => $client, 'carrier' => $carrier, 'agent' => $agent, 'source' => $source] = $this->resolve($request);

        if ($type === null || $client === null || $carrier === null || $source === null) {
            return null;
        }

        return [
            'class' => ['value' => $policyClass->value, 'label' => $policyClass->label()],
            'type' => ['value' => $type->value, 'label' => $type->label()],
            'client' => ['id' => $client->id, 'full_name' => $client->full_name],
            'carrier' => ['id' => $carrier->id, 'name' => $carrier->name],
            'agent' => $agent === null ? null : ['id' => $agent->id, 'full_name' => $agent->full_name],
            'source' => ['value' => $source->value, 'label' => $source->label()],
        ];
    }

    /**
     * The query that sends the user back to the first step for this class, keeping the choices that are still valid.
     *
     * @return array<string, int|string>
     */
    public function backToEntryQuery(Request $request, PolicyClass $policyClass): array
    {
        return array_filter(
            [...$this->selected($request), 'class' => $policyClass->value],
            fn (int|string|null $value): bool => $value !== null,
        );
    }

    /**
     * @return array{type: PolicyType|null, client: Client|null, carrier: Carrier|null, agent: Agent|null, source: PolicySource|null}
     */
    private function resolve(Request $request): array
    {
        /** @var PolicyType|null $type */
        $type = $this->enumValue($request, 'type', PolicyType::class);

        /** @var PolicySource|null $source */
        $source = $this->enumValue($request, 'source', PolicySource::class);

        return [
            'type' => $type,
            'client' => Client::query()->where('status', ClientStatus::Active)->find($this->id($request, 'client_id')),
            'carrier' => Carrier::query()->where('status', CarrierStatus::Active)->find($this->id($request, 'carrier_id')),
            'agent' => Agent::query()->where('status', AgentStatus::Active)->find($this->id($request, 'agent_id')),
            'source' => $source,
        ];
    }

    /**
     * Resolve a carried-over query value to an ID, ignoring anything that isn't a single value.
     */
    private function id(Request $request, string $key): ?int
    {
        return is_string($request->query($key)) ? $request->integer($key) : null;
    }

    /**
     * Resolve a carried-over query value to its enum case, ignoring anything that isn't one of the enum's cases.
     *
     * @param  class-string<BackedEnum>  $enumClass
     */
    private function enumValue(Request $request, string $key, string $enumClass): ?BackedEnum
    {
        $value = $request->query($key);

        return is_string($value) ? $enumClass::tryFrom($value) : null;
    }
}
