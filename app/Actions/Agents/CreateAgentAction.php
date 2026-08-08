<?php

declare(strict_types=1);

namespace App\Actions\Agents;

use App\Concerns\GeneratesUniqueSlug;
use App\Enums\AgentStatus;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateAgentAction
{
    use GeneratesUniqueSlug;

    /**
     * @param  array{first_name: string, last_name: string, date_of_birth: string, joined_at: string, phone: string, email: string, street?: string|null, building_floor?: string|null, country_id?: int|null, state_id?: int|null, city?: string|null}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Agent
    {
        return DB::transaction(function () use ($user, $attributes): Agent {
            $slug = $this->generateUniqueSlug(
                Agent::class,
                "{$attributes['first_name']} {$attributes['last_name']}",
                $user->organization_id,
            );

            /** @var Agent $agent */
            $agent = Agent::query()->create([
                ...$attributes,
                'status' => AgentStatus::Active,
                'organization_id' => $user->organization_id,
                'slug' => $slug,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            return $agent;
        });
    }
}
