<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Tab, Tabs } from '@/components/ui/tabs';
import {
    index as carriersIndex,
    show as carriersShow,
} from '@/routes/carriers';
import { index as carriersPoliciesIndex } from '@/routes/carriers/policies';
import type { CarrierResource } from './carrier';
import CarrierShowHeader from './CarrierShowHeader.vue';

const props = defineProps<{
    carrier: CarrierResource;
    policiesCount: number;
}>();

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
                <Tab :href="carriersShow(carrier.slug)">Overview</Tab>
                <Tab :href="carriersPoliciesIndex(carrier.slug)">Policies</Tab>
            </Tabs>
        </div>

        <slot />
    </div>
</template>
