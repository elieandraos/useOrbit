<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { show as automotiveShow } from '@/routes/policies/automotive';
import { show as expatShow } from '@/routes/policies/expat';
import { show as fireShow } from '@/routes/policies/fire';
import { show as lifeShow } from '@/routes/policies/life';
import { show as medicalShow } from '@/routes/policies/medical';
import { show as travelShow } from '@/routes/policies/travel';
import type { PolicyResource } from '@/types/policy';

const props = defineProps<{
    policies: PolicyResource[];
}>();

const classShowRoutes: Record<string, (slug: string) => { url: string }> = {
    medical: medicalShow,
    automotive: automotiveShow,
    expat: expatShow,
    fire: fireShow,
    life: lifeShow,
    travel: travelShow,
};

function showUrl(policy: PolicyResource): string {
    return classShowRoutes[policy.class](policy.slug).url;
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Policies up for renewal</CardTitle>
        </CardHeader>
        <CardContent
            v-if="props.policies.length === 0"
            class="text-[13px] text-tertiary"
        >
            No policies up for renewal yet.
        </CardContent>
        <CardContent v-else class="flex flex-col gap-2.5">
            <Link
                v-for="policy in props.policies"
                :key="policy.id"
                :href="showUrl(policy)"
                class="flex items-center justify-between gap-3 rounded-md border border-border-subtle px-3 py-2.5 transition-colors hover:bg-sunken"
            >
                <div class="min-w-0">
                    <div class="truncate text-[13px] font-medium text-primary">
                        {{ policy.policy_number }}
                    </div>
                    <div
                        class="mt-0.5 truncate font-mono text-[11.5px] text-tertiary"
                    >
                        {{ policy.client.full_name }}
                    </div>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-1">
                    <Badge tone="warning" dot>Renewing</Badge>
                    <span class="font-mono text-[11px] text-tertiary">
                        {{ policy.expiry_date_formatted }}
                    </span>
                </div>
            </Link>
        </CardContent>
    </Card>
</template>
