<?php

declare(strict_types=1);

namespace App\Support\Policies;

use App\Enums\PolicyDisplayStatus;
use App\Models\Policy;
use App\Support\Tenancy\OrganizationTimezone;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Resolves the display status of the current organization's policies against one organization-local "today".
 *
 * The date is memoized on the current request, so a list, an export or a filter looks the organization up once and
 * every row and the SQL filter share the same date.
 */
final readonly class PolicyDisplayStatusResolver
{
    private const string TODAY = 'policy_display_status.today';

    public function __construct(
        private OrganizationTimezone $organizationTimezone,
        private Request $request,
    ) {}

    /**
     * Get the current organization's local date, resolved once per request.
     */
    public function today(): CarbonImmutable
    {
        $today = $this->request->attributes->get(self::TODAY);

        if (! $today instanceof CarbonImmutable) {
            $today = $this->organizationTimezone->today();
            $this->request->attributes->set(self::TODAY, $today);
        }

        return $today;
    }

    public function for(Policy $policy): PolicyDisplayStatus
    {
        return PolicyDisplayStatus::resolve($policy->status, $policy->effective_date, $policy->expiry_date, $this->today());
    }
}
