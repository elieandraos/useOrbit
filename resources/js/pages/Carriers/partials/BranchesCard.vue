<script setup lang="ts">
import { ExternalLink, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { CarrierResource } from './carrier';

const props = defineProps<{
    carrier: CarrierResource;
}>();

const branch = computed(() => props.carrier.branch);

const addressLines = computed(() =>
    [
        branch.value?.building_floor,
        branch.value?.street,
        branch.value?.city,
        branch.value?.state_name,
        branch.value?.country_name,
    ].filter((line): line is string => !!line),
);

const mapsUrl = computed(
    () =>
        'https://www.google.com/maps/search/?api=1&query=' +
        encodeURIComponent(addressLines.value.join(', ')),
);
</script>

<template>
    <Card>
        <CardHeader bordered>
            <CardTitle>Branches · 1</CardTitle>
        </CardHeader>
        <CardContent v-if="branch" class="flex items-start gap-3.5 p-[18px]">
            <div
                class="flex size-9 shrink-0 items-center justify-center rounded-md bg-accent-bg text-accent"
            >
                <MapPin class="size-4" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[13.5px] font-semibold text-primary"
                        >Headquarters</span
                    >
                    <Badge tone="accent" dot>Headquarters</Badge>
                </div>
                <div
                    class="mt-1 text-[12.5px] leading-[1.55] text-secondary"
                >
                    <div v-for="line in addressLines" :key="line">
                        {{ line }}
                    </div>
                </div>
                <a
                    :href="mapsUrl"
                    target="_blank"
                    rel="noreferrer"
                    class="mt-2 inline-flex items-center gap-1 text-[11.5px] font-medium text-accent"
                >
                    <ExternalLink class="size-3" />
                    Open in Google Maps
                </a>

                <div
                    class="mt-2.5 flex items-center gap-2.5 border-t border-border-subtle pt-2.5"
                >
                    <Avatar :name="branch.contact_name" :size="26" />
                    <div class="min-w-0">
                        <div
                            class="flex flex-wrap items-center gap-1.5 text-[12.5px] font-medium text-primary"
                        >
                            {{ branch.contact_name }}
                            <Badge tone="neutral" dot>Primary contact</Badge>
                        </div>
                        <div
                            class="mt-0.5 flex flex-wrap gap-3 font-mono text-[11.5px] text-tertiary"
                        >
                            <span v-if="branch.contact_email">{{
                                branch.contact_email
                            }}</span>
                            <span v-if="branch.contact_phone">{{
                                branch.contact_phone
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
        <CardContent v-else class="text-center text-[13px] text-tertiary"
            >No branch on file.</CardContent
        >
    </Card>
</template>
