<?php

declare(strict_types=1);

use App\Enums\Gender;
use App\Enums\PolicyType;
use App\Http\Resources\PolicyMedicalResource;
use App\Models\Agent;
use App\Models\Organization;
use App\Models\Policy;
use App\Models\PolicyInsured;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $policy = Policy::factory()->medical()->create();

    $this->get(route('policies.medical.show', $policy))
        ->assertRedirect(route('login'));
});

test('authenticated user can view a policy from their organization', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.medical.show', $policy))
        ->assertOk()
        ->assertHasResource(
            'policy',
            PolicyMedicalResource::make($policy->load(['client', 'carrier', 'agent', 'medicalDetails']))
        );
});

test('authenticated user gets 404 for a policy from another organization', function () {
    $user = User::factory()->withOrganization()->create();

    $otherOrganization = Organization::factory()->create();
    $policy = Policy::factory()->medical()->create(['organization_id' => $otherOrganization->id]);

    $this->actingAs($user)
        ->get(route('policies.medical.show', $policy))
        ->assertNotFound();
});

test('authenticated user gets 404 for a non-medical policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->automotive()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('policies.medical.show', $policy))
        ->assertNotFound();
});

test('the policy exposes its assigned agent', function () {
    $user = User::factory()->withOrganization()->create();
    $agent = Agent::factory()->forOrganization($user)->create(['first_name' => 'Nadia', 'last_name' => 'Khoury']);
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'agent_id' => $agent->id,
    ]);

    $this->actingAs($user)
        ->get(route('policies.medical.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.agent.slug', $agent->slug)
            ->where('policy.agent.full_name', 'Nadia Khoury')
        );
});

test('a policy with no assigned agent exposes a null agent', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'agent_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('policies.medical.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('policy.agent', null));
});

test('a medical policy exposes its labeled and formatted detail fields', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create(['created_by' => $user->id]);
    $policy->medicalDetails->update([
        'coverage_scope' => 'in_out',
        'class_tier' => 'class_a',
        'co_insurance' => true,
        'co_insurance_share' => '20.00',
        'insured_gender' => Gender::Female,
        'insured_date_of_birth' => '1990-05-14',
    ]);

    $this->actingAs($user)
        ->get(route('policies.medical.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('policy.details.coverage_scope', 'in_out')
            ->where('policy.details.coverage_scope_label', 'In-Out')
            ->where('policy.details.class_tier_label', 'Class A')
            ->where('policy.details.co_insurance', true)
            ->where('policy.details.co_insurance_share', '20.00')
            ->where('policy.details.insured_gender', 'female')
            ->where('policy.details.insured_gender_label', 'Female')
            ->where('policy.details.insured_date_of_birth', '1990-05-14')
            ->where('policy.details.insured_date_of_birth_formatted', 'May 14, 1990')
        );
});

test('insureds are present for a group policy and expose labeled and formatted fields', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => PolicyType::Group,
    ]);
    PolicyInsured::factory()->for($policy)->create([
        'full_name' => 'Marc Aoun',
        'gender' => Gender::Male,
        'date_of_birth' => '1985-03-02',
    ]);

    $this->actingAs($user)
        ->get(route('policies.medical.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('policy.insureds', 1)
            ->where('policy.insureds.0.full_name', 'Marc Aoun')
            ->where('policy.insureds.0.gender', 'male')
            ->where('policy.insureds.0.gender_label', 'Male')
            ->where('policy.insureds.0.date_of_birth', '1985-03-02')
            ->where('policy.insureds.0.date_of_birth_formatted', 'Mar 2, 1985')
        );
});

test('insureds are absent for a single policy', function () {
    $user = User::factory()->withOrganization()->create();
    $policy = Policy::factory()->forOrganization($user)->medical()->create([
        'created_by' => $user->id,
        'type' => PolicyType::Single,
    ]);

    $this->actingAs($user)
        ->get(route('policies.medical.show', $policy))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('policy.insureds'));
});
