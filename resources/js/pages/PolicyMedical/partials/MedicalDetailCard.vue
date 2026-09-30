<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DetailField from '@/pages/Clients/partials/DetailField.vue';
import { index as policiesMembersIndex } from '@/routes/policies/members';
import type { PolicyMedicalResource } from './policy';

const props = defineProps<{
    policy: PolicyMedicalResource;
}>();

const coveredMembersCount = props.policy.insureds?.length ?? 0;

const coInsuranceValue = props.policy.details.co_insurance
    ? `Yes (${props.policy.details.co_insurance_share}%)`
    : 'No';
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Medical detail</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                <DetailField
                    label="Coverage scope"
                    :value="policy.details.coverage_scope_label"
                />
                <DetailField
                    label="Plan tier"
                    :value="policy.details.class_tier_label"
                />
                <DetailField label="Co-insurance" :value="coInsuranceValue" />
                <DetailField
                    label="Guaranteed renewable"
                    :value="policy.details.guaranteed_renewable ? 'Yes' : 'No'"
                />

                <template v-if="policy.type === 'single'">
                    <DetailField
                        label="Insured"
                        :value="policy.details.insured_full_name"
                    />
                    <DetailField
                        label="Date of birth"
                        :value="policy.details.insured_date_of_birth_formatted"
                    />
                    <DetailField
                        label="Gender"
                        :value="policy.details.insured_gender_label"
                    />
                    <DetailField
                        label="Smoker"
                        :value="policy.details.insured_smoker ? 'Yes' : 'No'"
                    />
                </template>
                <template v-else>
                    <div class="flex min-w-0 flex-col gap-1">
                        <p
                            class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
                        >
                            Covered members
                        </p>
                        <Link
                            :href="policiesMembersIndex(policy.slug)"
                            class="inline-flex w-fit items-center gap-1 text-[13.5px] font-medium text-accent underline-offset-4 hover:underline"
                        >
                            {{ coveredMembersCount }}
                            {{
                                coveredMembersCount === 1 ? 'member' : 'members'
                            }}
                            <ArrowRight class="size-3.5" />
                        </Link>
                    </div>
                </template>
            </div>
        </CardContent>
    </Card>
</template>
