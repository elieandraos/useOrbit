<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent } from '@/components/ui/card';
import { create as policiesCreate } from '@/routes/policies';
import type { Paginated } from '@/types';
import type { PolicyResource } from '@/types/policy';
import type { ClientResource } from '../Clients/partials/client';
import ClientDetailShell from '../Clients/partials/ClientDetailShell.vue';
import PoliciesTable from '../Policies/partials/PoliciesTable.vue';

const props = defineProps<{
    client: ClientResource;
    policies: Paginated<PolicyResource>;
}>();

const hasPolicies = computed(() => props.policies.data.length > 0);
</script>

<template>
    <ClientDetailShell :client="client" :policies-count="policies.meta.total">
        <Head :title="`${client.full_name} · Policies`" />

        <div class="mt-6 flex-1">
            <PoliciesTable v-if="hasPolicies" :policies="policies" />
            <Card v-else>
                <CardContent
                    class="flex flex-col items-center gap-4 py-16 text-center text-[13px] text-tertiary"
                >
                    No policies for this client yet.
                    <Link
                        :href="
                            policiesCreate.url({
                                query: { client_id: client.id },
                            })
                        "
                    >
                        <Button variant="primary" size="md">
                            <template #leading><Plus /></template>
                            New Policy
                        </Button>
                    </Link>
                </CardContent>
            </Card>
        </div>
    </ClientDetailShell>
</template>
