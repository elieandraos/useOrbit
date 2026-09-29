<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PolicyClass;
use App\Enums\PolicySource;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyAutomotiveDetails;
use App\Models\PolicyExpatDetails;
use App\Models\PolicyFireDetails;
use App\Models\PolicyLifeDetails;
use App\Models\PolicyMedicalDetails;
use App\Models\PolicyTravelDetails;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Policy>
 */
class PolicyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     *
     * @throws \DateMalformedStringException
     */
    public function definition(): array
    {
        $effectiveDate = fake()->dateTimeBetween('-1 year');
        $expiryDate = (clone $effectiveDate)->modify('+1 year');

        return [
            'organization_id' => Organization::factory(),
            'slug' => null,
            'policy_number' => null,
            'class' => fake()->randomElement(PolicyClass::cases())->value,
            'subclass' => function (array $attributes): string {
                $class = $attributes['class'] instanceof PolicyClass ? $attributes['class'] : PolicyClass::from($attributes['class']);

                return fake()->randomElement($class->subclasses());
            },
            'type' => fake()->randomElement(PolicyType::cases())->value,
            'client_id' => Client::factory(),
            'carrier_id' => Carrier::factory(),
            'agent_id' => null,
            'effective_date' => $effectiveDate->format('Y-m-d'),
            'expiry_date' => $expiryDate->format('Y-m-d'),
            'bound_at' => $effectiveDate->format('Y-m-d'),
            'premium_amount' => fake()->randomFloat(2, 200, 5000),
            'discount_amount' => 0,
            'status' => PolicyStatus::Active->value,
            'source' => fake()->randomElement(PolicySource::cases())->value,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Derive slug/policy_number from the final policy_number (after any factory
     * state or ->create([...]) override), not the random values computed in definition().
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Policy $policy): void {
            $policy->policy_number ??= 'POL-'.fake()->unique()->numerify('####');
            $policy->slug ??= Str::slug($policy->policy_number.'-'.fake()->unique()->numerify());
        });
    }

    public function forOrganization(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $user->organization_id,
        ]);
    }

    public function medical(): static
    {
        return $this->state(fn (array $attributes): array => [
            'class' => PolicyClass::Medical->value,
        ])->afterCreating(function (Policy $policy): void {
            PolicyMedicalDetails::factory()->for($policy)->create();
        });
    }

    public function automotive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'class' => PolicyClass::Automotive->value,
        ])->afterCreating(function (Policy $policy): void {
            $isAllRisk = $policy->subclass === 'All Risk';

            PolicyAutomotiveDetails::factory()->for($policy)->create([
                'valuation_amount' => $isAllRisk ? fake()->randomFloat(2, 5000, 150000) : null,
                'valuation_source' => $isAllRisk ? fake()->randomElement(['Carrier assessor', 'Independent appraisal', 'Market value']) : null,
            ]);
        });
    }

    public function expat(): static
    {
        return $this->state(fn (array $attributes): array => [
            'class' => PolicyClass::Expat->value,
        ])->afterCreating(function (Policy $policy): void {
            PolicyExpatDetails::factory()->for($policy)->create();
        });
    }

    public function fire(): static
    {
        return $this->state(fn (array $attributes): array => [
            'class' => PolicyClass::Fire->value,
        ])->afterCreating(function (Policy $policy): void {
            PolicyFireDetails::factory()->for($policy)->create();
        });
    }

    public function life(): static
    {
        return $this->state(fn (array $attributes): array => [
            'class' => PolicyClass::Life->value,
        ])->afterCreating(function (Policy $policy): void {
            PolicyLifeDetails::factory()->for($policy)->create();
        });
    }

    public function travel(): static
    {
        return $this->state(fn (array $attributes): array => [
            'class' => PolicyClass::Travel->value,
        ])->afterCreating(function (Policy $policy): void {
            PolicyTravelDetails::factory()->for($policy)->create();
        });
    }
}
