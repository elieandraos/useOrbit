<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Organization;
use Carbon\CarbonImmutable;

/**
 * Resolves an organization's effective timezone and its local calendar "today".
 *
 * Only the derived calendar date follows the organization: `app.timezone` and stored timestamps stay
 * UTC, and stored calendar dates (a policy's effective and expiry dates) are never converted.
 */
final readonly class OrganizationTimezone
{
    /**
     * The timezone an organization without one of its own resolves to.
     */
    public const string FALLBACK = 'UTC';

    public function __construct(private OrganizationContext $context) {}

    /**
     * Get the organization's effective timezone: its stored identifier, or UTC when unset.
     */
    public function for(Organization $organization): string
    {
        return $organization->timezone ?? self::FALLBACK;
    }

    /**
     * Get the organization-local date, as the start of today in its effective timezone.
     */
    public function todayFor(Organization $organization): CarbonImmutable
    {
        return CarbonImmutable::now($this->for($organization))->startOfDay();
    }

    /**
     * Get the effective timezone of the organization in the current request's context.
     */
    public function current(): string
    {
        return $this->for($this->currentOrganization());
    }

    /**
     * Get the local date of the organization in the current request's context.
     */
    public function today(): CarbonImmutable
    {
        return $this->todayFor($this->currentOrganization());
    }

    private function currentOrganization(): Organization
    {
        /** @var Organization $organization */
        $organization = Organization::query()->findOrFail($this->context->id());

        return $organization;
    }
}
