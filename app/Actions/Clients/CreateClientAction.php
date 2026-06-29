<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateClientAction
{
    /**
     * @param  array{first_name: string, last_name: string, phone: string, date_of_birth: string, gender: string, enrollment_date: string, lead_source: string, status: string, middle_name?: string|null, mothers_name?: string|null, email?: string|null, photo?: string|null, street?: string|null, building_floor?: string|null, city?: string|null, state?: string|null, country_id?: int|null, emergency_contact_name?: string|null, emergency_contact_relationship?: string|null, emergency_contact_phone?: string|null}  $attributes
     */
    public function handle(User $user, array $attributes): Client
    {
        return DB::transaction(function () use ($user, $attributes): Client {
            $slug = $this->generateUniqueSlug($attributes['first_name'], $attributes['last_name']);

            /** @var Client $client */
            $client = Client::query()->create([
                ...$attributes,
                'organization_id' => $user->current_organization_id,
                'slug' => $slug,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            return $client;
        });
    }

    private function generateUniqueSlug(string $firstName, string $lastName): string
    {
        $base = Str::slug($firstName.'-'.$lastName);
        $slug = $base;
        $counter = 1;

        while (Client::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
