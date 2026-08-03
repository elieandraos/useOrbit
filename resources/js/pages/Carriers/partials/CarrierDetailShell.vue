<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Tabs } from '@/components/ui/tabs';
import { index as carriersIndex } from '@/routes/carriers';
import type { CarrierResource } from './carrier';
import CarrierShowHeader from './CarrierShowHeader.vue';

const props = defineProps<{
    carrier: CarrierResource;
    policiesCount: number;
}>();

const tab = ref<'overview' | 'policies'>('overview');

setLayoutProps({
    breadcrumbs: [
        {
            title: 'Carriers',
            href: carriersIndex(),
        },
        {
            title: props.carrier.name,
        },
    ],
    breadcrumbsSurface: true,
});
</script>

<template>
    <Head :title="carrier.name" />

    <div class="flex flex-1 flex-col">
        <CarrierShowHeader :carrier="carrier" :policies-count="policiesCount" />

        <div
            class="-mx-4 overflow-x-auto border-b border-border-subtle bg-surface px-4 sm:mx-0 sm:mt-6 sm:overflow-visible sm:border-0 sm:bg-transparent sm:px-0"
        >
            <Tabs class="min-w-max">
                <button
                    type="button"
                    class="cursor-pointer border-b-2 px-3 py-2 text-sm font-medium transition-colors"
                    :class="
                        tab === 'overview'
                            ? 'border-accent text-accent'
                            : 'border-transparent text-secondary hover:text-primary'
                    "
                    @click="tab = 'overview'"
                >
                    Overview
                </button>
                <button
                    type="button"
                    class="cursor-pointer border-b-2 px-3 py-2 text-sm font-medium transition-colors"
                    :class="
                        tab === 'policies'
                            ? 'border-accent text-accent'
                            : 'border-transparent text-secondary hover:text-primary'
                    "
                    @click="tab = 'policies'"
                >
                    Policies
                </button>
            </Tabs>
        </div>

        <slot v-if="tab === 'overview'" />
        <div v-else class="py-12 text-center text-secondary">Coming soon</div>
    </div>
</template>
