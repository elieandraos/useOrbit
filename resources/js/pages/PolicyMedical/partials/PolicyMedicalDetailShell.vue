<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Tab, Tabs } from '@/components/ui/tabs';
import { index as policiesIndex } from '@/routes/policies';
import { index as policiesDocumentsIndex } from '@/routes/policies/documents';
import { show as policiesMedicalShow } from '@/routes/policies/medical';
import { index as policiesNotesIndex } from '@/routes/policies/notes';
import type { PolicyResource } from '@/types/policy';
import PolicyMedicalShowHeader from './PolicyMedicalShowHeader.vue';

const props = defineProps<{
    policy: PolicyResource;
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Policies',
            href: policiesIndex(),
        },
        {
            title: props.policy.policy_number,
        },
    ],
    breadcrumbsSurface: true,
});
</script>

<template>
    <Head :title="policy.policy_number" />

    <div class="flex flex-1 flex-col">
        <PolicyMedicalShowHeader :policy="policy" />

        <div
            class="-mx-4 overflow-x-auto border-b border-border-subtle bg-surface px-4 sm:mx-0 sm:mt-6 sm:overflow-visible sm:border-0 sm:bg-transparent sm:px-0"
        >
            <Tabs class="min-w-max">
                <Tab :href="policiesMedicalShow(policy.slug).url">Overview</Tab>
                <Tab v-if="policy.type === 'group'" href="#">Members</Tab>
                <Tab href="#">Settlements</Tab>
                <Tab :href="policiesDocumentsIndex(policy.slug).url"
                    >Documents</Tab
                >
                <Tab :href="policiesNotesIndex(policy.slug).url">Notes</Tab>
            </Tabs>
        </div>

        <slot />
    </div>
</template>
