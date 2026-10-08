<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, Plus, Shield } from '@lucide/vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatMoney } from '@/lib/money';
import { policyClassRoutes } from '@/lib/policyClassRoutes';
import { policyStatusTone } from '@/lib/policyStatusTone';
import { create as policiesCreate } from '@/routes/policies';
import type { PolicyResource } from '@/types/policy';
import type { ClientResource } from './client';

const props = defineProps<{
    client: ClientResource;
    policies: PolicyResource[];
}>();
</script>

<template>
    <Card>
        <CardHeader bordered>
            <CardTitle>Policies</CardTitle>
            <CardAction>
                <Link
                    :href="
                        policiesCreate.url({
                            query: { client_id: props.client.id },
                        })
                    "
                >
                    <Button variant="ghost" size="sm">
                        <template #leading><Plus /></template>
                        Add policy
                    </Button>
                </Link>
            </CardAction>
        </CardHeader>
        <CardContent v-if="policies.length > 0" class="p-0">
            <Link
                v-for="policy in policies"
                :key="policy.id"
                :href="policyClassRoutes[policy.class].show(policy.slug).url"
                class="flex items-center gap-3.5 border-b border-border-subtle px-6 py-3 transition-colors last:border-b-0 hover:bg-sunken"
            >
                <div
                    class="flex size-8 shrink-0 items-center justify-center rounded-[10px] bg-accent-bg text-accent"
                >
                    <Shield class="size-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex min-w-0 items-center gap-2">
                        <span
                            class="truncate text-[13.5px] font-semibold text-primary"
                            >{{ policy.subclass }}</span
                        >
                        <Badge
                            :tone="policyStatusTone[policy.display_status]"
                            dot
                            >{{ policy.display_status_label }}</Badge
                        >
                    </div>
                    <p
                        class="mt-0.5 truncate font-mono text-[11.5px] text-tertiary"
                    >
                        {{ policy.policy_number }} · {{ policy.carrier.name }}
                    </p>
                </div>
                <p
                    class="shrink-0 font-mono text-[13px] font-medium text-primary"
                >
                    {{ formatMoney(policy.net_premium, policy.currency_code) }}
                </p>
                <ChevronRight class="size-4 shrink-0 text-tertiary" />
            </Link>
        </CardContent>
        <CardContent v-else class="text-center text-[13px] text-tertiary"
            >No policies yet.</CardContent
        >
    </Card>
</template>
