<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Concerns\GeneratesUniqueSlug;
use App\Enums\ClientStatus;
use App\Enums\ClientType;
use App\Models\Client;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Support\Facades\DB;

final class CreateClientAction
{
    use GeneratesUniqueSlug;

    public function __construct(private readonly OrganizationContext $organizationContext) {}

    /**
     * @param  array{client_type: string, company_name?: string|null, first_name: string, last_name: string, phone: string, date_of_birth?: string|null, gender?: string|null, enrollment_date: string, lead_source: string, middle_name?: string|null, mothers_name?: string|null, email?: string|null, photo?: string|null, street?: string|null, building_floor?: string|null, country_id?: int|null, state_id?: int|null, city?: string|null, emergency_contact_name?: string|null, emergency_contact_relationship?: string|null, emergency_contact_phone?: string|null}  $attributes
     *
     * @throws \Throwable
     */
    public function handle(User $user, array $attributes): Client
    {
        return DB::transaction(function () use ($user, $attributes): Client {
            $organizationId = $this->organizationContext->id();

            $isCompany = $attributes['client_type'] === ClientType::Company->value;

            $nameSource = $isCompany
                ? $attributes['company_name']
                : "{$attributes['first_name']} {$attributes['last_name']}";

            $slug = $this->generateUniqueSlug(
                Client::class,
                $nameSource,
                $organizationId,
            );

            /** @var Client $client */
            $client = Client::query()->create([
                ...$attributes,
                'status' => ClientStatus::Active,
                'organization_id' => $organizationId,
                'slug' => $slug,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            return $client;
        });
    }
}
