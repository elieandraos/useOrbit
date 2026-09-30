<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Carrier;
use App\Models\Client;
use App\Models\State;

/**
 * Builds policy request payloads for the HTTP and action tests: the base policy fields plus one class slice.
 *
 * Overrides merge into nested class slices key by key, while a list (such as `insureds`) replaces the default list outright.
 */
final class PolicyPayload
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function base(Client $client, Carrier $carrier, array $overrides = []): array
    {
        return self::merge([
            'policy_number' => 'POL-'.fake()->unique()->numerify('#####'),
            'class' => 'medical',
            'subclass' => 'Hospitalization',
            'type' => 'single',
            'client_id' => $client->id,
            'carrier_id' => $carrier->id,
            'agent_id' => null,
            'effective_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'premium_amount' => '1200.00',
            'discount_amount' => null,
            'status' => 'active',
            'source' => 'client',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function automotive(Client $client, Carrier $carrier, array $overrides = []): array
    {
        return self::base($client, $carrier, self::merge([
            'class' => 'automotive',
            'subclass' => 'Third Party Liability',
            'premium_amount' => '800.00',
            'automotive' => [
                'plate_number' => '123 AB',
                'make' => 'Toyota',
                'model' => 'Corolla',
                'year' => 2022,
                'valuation_amount' => null,
                'valuation_source' => null,
            ],
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function automotiveAllRisk(Client $client, Carrier $carrier, array $overrides = []): array
    {
        return self::automotive($client, $carrier, self::merge([
            'subclass' => 'All Risk',
            'automotive' => [
                'valuation_amount' => '35000.00',
                'valuation_source' => 'Carrier assessor',
            ],
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function expat(Client $client, Carrier $carrier, array $overrides = []): array
    {
        return self::base($client, $carrier, self::merge([
            'class' => 'expat',
            'subclass' => 'Worldwide',
            'expat' => [
                'coverage_zone' => 'in',
                'travel_scope' => null,
                'full_name' => 'Karim Saad',
                'gender' => 'male',
                'nationality' => 'Lebanese',
                'date_of_birth' => '1985-04-12',
                'phone' => '+96170123456',
            ],
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function expatInOut(Client $client, Carrier $carrier, array $overrides = []): array
    {
        return self::expat($client, $carrier, self::merge([
            'expat' => [
                'coverage_zone' => 'in_out',
                'travel_scope' => 'Worldwide',
            ],
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function fire(Client $client, Carrier $carrier, State $state, array $overrides = []): array
    {
        return self::base($client, $carrier, self::merge([
            'class' => 'fire',
            'subclass' => 'Building',
            'premium_amount' => '600.00',
            'fire' => [
                'property_type' => 'Residential apartment',
                'floor_area' => 180,
                'year_built' => 2005,
                'street' => 'Hamra Street',
                'building_floor' => 'Floor 3',
                'city' => 'Beirut',
                'state_id' => $state->id,
                'country_id' => $state->country_id,
                'sum_insured' => '250000.00',
            ],
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function life(Client $client, Carrier $carrier, array $overrides = []): array
    {
        return self::base($client, $carrier, self::merge([
            'class' => 'life',
            'subclass' => 'Term',
            'premium_amount' => '600.00',
            'life' => [
                'sum_assured' => '150000.00',
                'term_years' => 20,
                'smoker' => false,
                'beneficiaries' => 'Jane Doe (100%)',
            ],
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function medicalSingle(Client $client, Carrier $carrier, array $overrides = []): array
    {
        return self::base($client, $carrier, self::merge([
            'class' => 'medical',
            'subclass' => 'Hospitalization',
            'type' => 'single',
            'medical' => [
                'coverage_scope' => 'in',
                'class_tier' => 'class_a',
                'co_insurance' => false,
                'guaranteed_renewable' => true,
                'insured_full_name' => 'Amelia Hartwell',
                'insured_date_of_birth' => '1986-03-22',
                'insured_gender' => 'female',
                'insured_smoker' => false,
            ],
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function medicalGroup(Client $client, Carrier $carrier, array $overrides = []): array
    {
        return self::base($client, $carrier, self::merge([
            'class' => 'medical',
            'subclass' => 'Outpatient',
            'type' => 'group',
            'medical' => [
                'coverage_scope' => 'in_out',
                'class_tier' => 'class_b',
                'co_insurance' => false,
                'guaranteed_renewable' => true,
            ],
            'insureds' => [self::insured()],
        ], $overrides));
    }

    /**
     * One submitted group Medical member.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function insured(array $overrides = []): array
    {
        return self::merge([
            'full_name' => 'Lina Hartwell',
            'relationship' => 'Spouse',
            'date_of_birth' => '1988-08-08',
            'gender' => 'female',
            'medical_notes' => null,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function travel(Client $client, Carrier $carrier, array $overrides = []): array
    {
        return self::base($client, $carrier, self::merge([
            'class' => 'travel',
            'subclass' => 'Schengen',
            'premium_amount' => '150.00',
            'travel' => [
                'destination' => 'Portugal',
                'trip_start_date' => '2026-06-01',
                'trip_end_date' => '2026-06-15',
                'travelers' => 'Jane Doe, John Doe',
                'coverage_tier' => 'Standard',
            ],
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private static function merge(array $defaults, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            $mergesIntoSlice = is_array($value)
                && isset($defaults[$key])
                && is_array($defaults[$key])
                && ! array_is_list($defaults[$key])
                && ! array_is_list($value);

            $defaults[$key] = $mergesIntoSlice ? self::merge($defaults[$key], $value) : $value;
        }

        return $defaults;
    }
}
