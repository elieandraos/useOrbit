<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Tab, Tabs } from '@/components/ui/tabs';
import { index as policiesIndex } from '@/routes/policies';
import { index as policiesDocumentsIndex } from '@/routes/policies/documents';
import { show as policiesExpatShow } from '@/routes/policies/expat';
import { index as policiesNotesIndex } from '@/routes/policies/notes';
import type { PolicyResource } from '@/types/policy';
import PolicyExpatShowHeader from './PolicyExpatShowHeader.vue';

const props = defineProps<{
    policy: PolicyResource;
}>();

const isSettlementsTabActive = ref(false);

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
        <PolicyExpatShowHeader :policy="policy" />

        <div
            class="-mx-4 overflow-x-auto border-b border-border-subtle bg-surface px-4 sm:mx-0 sm:mt-6 sm:overflow-visible sm:border-0 sm:bg-transparent sm:px-0"
        >
            <Tabs class="min-w-max">
                <Tab :href="policiesExpatShow(policy.slug).url">Overview</Tab>
                <button
                    type="button"
                    class="cursor-pointer border-b-2 px-3 py-2 text-sm font-medium transition-colors"
                    :class="
                        isSettlementsTabActive
                            ? 'border-accent text-accent'
                            : 'border-transparent text-secondary hover:text-primary'
                    "
                    @click="isSettlementsTabActive = true"
                >
                    Settlements
                </button>
                <Tab :href="policiesDocumentsIndex(policy.slug).url"
                    >Documents</Tab
                >
                <Tab :href="policiesNotesIndex(policy.slug).url">Notes</Tab>
            </Tabs>
        </div>

        <slot v-if="!isSettlementsTabActive" />
        <div v-else class="py-12 text-center text-secondary">Coming soon</div>
    </div>
</template>
