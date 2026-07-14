<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Concerns\GeneratesUniqueSlug;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateClientAction
{
    use GeneratesUniqueSlug;

    /**
     * @param  array{first_name: string, last_name: string, phone: string, date_of_birth: string, gender: string, enrollment_date: string, lead_source: string, middle_name?: string|null, mothers_name?: string|null, email?: string|null, photo?: string|null, street?: string|null, building_floor?: string|null, country_id?: int|null, state_id?: int|null, city_id?: int|null, emergency_contact_name?: string|null, emergency_contact_relationship?: string|null, emergency_contact_phone?: string|null}  $attributes
     */
    public function handle(User $user, Client $client, array $attributes): Client
    {
        return DB::transaction(function () use ($user, $client, $attributes): Client {
            $nameChanged = $attributes['first_name'] !== $client->first_name
                || $attributes['last_name'] !== $client->last_name;

            if ($nameChanged) {
                $attributes['slug'] = $this->generateUniqueSlug(
                    Client::class,
                    "{$attributes['first_name']} {$attributes['last_name']}",
                    $user->current_organization_id,
                    $client->id,
                );
            }

            $client->update([
                ...$attributes,
                'updated_by' => $user->id,
            ]);

            return $client->fresh();
        });
    }
}
