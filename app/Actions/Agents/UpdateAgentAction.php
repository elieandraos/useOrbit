<?php

declare(strict_types=1);

namespace App\Actions\Agents;

use App\Concerns\GeneratesUniqueSlug;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateAgentAction
{
    use GeneratesUniqueSlug;

    /**
     * @param  array{first_name: string, last_name: string, date_of_birth: string, joined_at: string, phone: string, email: string, street?: string|null, building_floor?: string|null, country_id?: int|null, state_id?: int|null, city?: string|null}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, Agent $agent, array $attributes): Agent
    {
        return DB::transaction(function () use ($user, $agent, $attributes): Agent {
            $nameChanged = $attributes['first_name'] !== $agent->first_name
                || $attributes['last_name'] !== $agent->last_name;

            $agent->update([
                ...$attributes,
                'updated_by' => $user->id,
                ...$nameChanged ? [
                    'slug' => $this->generateUniqueSlug(
                        Agent::class,
                        "{$attributes['first_name']} {$attributes['last_name']}",
                        $user->organization_id,
                        $agent->id,
                    ),
                ] : [],
            ]);

            return $agent->fresh();
        });
    }
}
