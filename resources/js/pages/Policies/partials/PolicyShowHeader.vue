<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Download, Pencil } from '@lucide/vue';
import { computed } from 'vue';
import PolicyClassTile from '@/components/policies/PolicyClassTile.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { IdentityLink } from '@/components/ui/identity-link';
import { Spinner } from '@/components/ui/spinner';
import { useFileExport } from '@/composables/useFileExport';
import { policyClassRoutes } from '@/lib/policyClassRoutes';
import { policyStatusTone } from '@/lib/policyStatusTone';
import { show as carriersShow } from '@/routes/carriers';
import { show as clientsShow } from '@/routes/clients';
import type { PolicyResource } from '@/types/policy';

const props = defineProps<{
    policy: PolicyResource;
}>();

const { isExporting, exportFile } = useFileExport();

const classRoutes = computed(() => policyClassRoutes[props.policy.class]);

const exportUrl = computed(
    () => classRoutes.value.exportPdf(props.policy.slug).url,
);

function exportPolicy(): Promise<void> {
    return exportFile(exportUrl.value, `${props.policy.slug}.pdf`, {
        success: 'Policy exported.',
        error: 'Failed to export policy. Please try again.',
    });
}
</script>

<template>
    <div class="pb-0 sm:pb-6">
        <!-- Mobile: centered hero, bled edge-to-edge to match AppContent's mobile padding -->
        <div
            class="-mx-4 flex flex-col items-center border-b border-border-subtle bg-surface px-4 py-6 sm:hidden"
        >
            <PolicyClassTile :policy="policy" size="xl" />

            <h1 class="mt-3 text-lg font-semibold text-primary">
                {{ policy.policy_number }}
            </h1>

            <div
                class="mt-1.5 flex flex-wrap items-center justify-center gap-2"
            >
                <Badge :tone="policyStatusTone[policy.status] ?? 'neutral'" dot>
                    {{ policy.status_label }}
                </Badge>
                <Badge tone="accent">{{ policy.type_label }}</Badge>
            </div>

            <div
                class="mt-2 flex max-w-full flex-wrap items-center justify-center gap-x-4 gap-y-1 text-center text-[13px] text-secondary"
            >
                <span>{{ policy.class_label }} · {{ policy.subclass }}</span>
                <IdentityLink :href="clientsShow(policy.client.slug)">
                    {{ policy.client.full_name }}
                </IdentityLink>
                <IdentityLink :href="carriersShow(policy.carrier.slug)">
                    {{ policy.carrier.name }}
                </IdentityLink>
            </div>

            <div class="mt-5 flex items-center gap-2">
                <Button
                    variant="secondary"
                    size="sm"
                    :disabled="isExporting"
                    @click="exportPolicy"
                >
                    <template #leading>
                        <Spinner v-if="isExporting" />
                        <Download v-else />
                    </template>
                    Export
                </Button>
                <Link :href="classRoutes.edit(policy.slug).url">
                    <Button variant="secondary" size="sm">
                        <template #leading><Pencil /></template>
                        Edit
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Desktop (`sm` and above): tile beside the title, actions on the right -->
        <div class="hidden items-start justify-between gap-4 sm:flex">
            <div class="flex min-w-0 items-start gap-4">
                <PolicyClassTile :policy="policy" size="lg" />

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl font-semibold text-primary">
                            {{ policy.policy_number }}
                        </h1>
                        <Badge
                            :tone="policyStatusTone[policy.status] ?? 'neutral'"
                            dot
                        >
                            {{ policy.status_label }}
                        </Badge>
                        <Badge tone="accent">{{ policy.type_label }}</Badge>
                    </div>
                    <div
                        class="mt-1.5 flex flex-wrap items-center gap-4 text-[13px] text-secondary"
                    >
                        <span
                            >{{ policy.class_label }} ·
                            {{ policy.subclass }}</span
                        >
                        <IdentityLink :href="clientsShow(policy.client.slug)">
                            {{ policy.client.full_name }}
                        </IdentityLink>
                        <IdentityLink :href="carriersShow(policy.carrier.slug)">
                            {{ policy.carrier.name }}
                        </IdentityLink>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Button
                    variant="secondary"
                    size="md"
                    :disabled="isExporting"
                    @click="exportPolicy"
                >
                    <template #leading>
                        <Spinner v-if="isExporting" />
                        <Download v-else />
                    </template>
                    Export
                </Button>
                <Link :href="classRoutes.edit(policy.slug).url">
                    <Button variant="secondary" size="md">
                        <template #leading><Pencil /></template>
                        Edit
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
