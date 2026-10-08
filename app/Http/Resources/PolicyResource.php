<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Policy;
use App\Support\Policies\PolicyDisplayStatusResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Policy */
final class PolicyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->baseAttributes(),
            'insureds' => PolicyInsuredResource::collection($this->whenLoaded('insureds')),
        ];
    }

    /**
     * The canonical policy representation shared by every policy class resource.
     *
     * Conditional values are returned unresolved so the composing resource still drops unloaded relations.
     *
     * @return array<string, mixed>
     */
    public function baseAttributes(): array
    {
        $displayStatus = app(PolicyDisplayStatusResolver::class)->for($this->resource);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'policy_number' => $this->policy_number,
            'class' => $this->class,
            'class_label' => $this->class->label(),
            'subclass' => $this->subclass,
            'type' => $this->type,
            'type_label' => $this->type->label(),
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'slug' => $this->client->slug,
                'full_name' => $this->client->full_name,
            ]),
            'carrier' => $this->whenLoaded('carrier', fn () => [
                'id' => $this->carrier->id,
                'slug' => $this->carrier->slug,
                'name' => $this->carrier->name,
            ]),
            'carrier_branch' => $this->whenLoaded('carrierBranch', fn () => $this->carrierBranch === null ? null : [
                'id' => $this->carrierBranch->id,
                'label' => $this->carrierBranch->label,
            ]),
            'agent' => $this->whenLoaded('agent', fn () => $this->agent === null ? null : [
                'id' => $this->agent->id,
                'slug' => $this->agent->slug,
                'full_name' => $this->agent->full_name,
            ]),
            'currency_id' => $this->currency_id,
            'currency_code' => $this->whenLoaded('currency', fn () => $this->currency->code),
            'effective_date' => $this->effective_date->format('Y-m-d'),
            'effective_date_formatted' => $this->effective_date->format('M j, Y'),
            'expiry_date' => $this->expiry_date->format('Y-m-d'),
            'expiry_date_formatted' => $this->expiry_date->format('M j, Y'),
            'premium_amount' => $this->premium_amount,
            'discount_amount' => $this->discount_amount,
            'net_premium' => number_format((float) $this->premium_amount - (float) $this->discount_amount, 2, '.', ''),
            'display_status' => $displayStatus,
            'display_status_label' => $displayStatus->label(),
            'source' => $this->source,
            'source_label' => $this->source->label(),
        ];
    }
}
