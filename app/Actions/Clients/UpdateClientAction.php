<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Concerns\GeneratesUniqueSlug;
use App\Enums\ClientType;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateClientAction
{
    use GeneratesUniqueSlug;

    /**
     * @param  array{company_name?: string|null, first_name: string, last_name: string, phone: string, date_of_birth?: string|null, gender?: string|null, enrollment_date: string, lead_source: string, middle_name?: string|null, mothers_name?: string|null, email?: string|null, photo?: string|null, street?: string|null, building_floor?: string|null, country_id?: int|null, state_id?: int|null, city?: string|null, emergency_contact_name?: string|null, emergency_contact_relationship?: string|null, emergency_contact_phone?: string|null}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, Client $client, array $attributes): Client
    {
        return DB::transaction(function () use ($user, $client, $attributes): Client {
            $isCompany = $client->client_type === ClientType::Company;

            $existingName = $isCompany
                ? $client->company_name
                : "$client->first_name $client->last_name";

            $newName = $isCompany
                ? $attributes['company_name']
                : "{$attributes['first_name']} {$attributes['last_name']}";

            $nameChanged = $newName !== $existingName;

            if ($nameChanged) {
                $attributes['slug'] = $this->generateUniqueSlug(
                    Client::class,
                    $newName,
                    $user->organization_id,
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
