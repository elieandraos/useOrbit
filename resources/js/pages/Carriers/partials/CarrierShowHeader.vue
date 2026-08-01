<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Globe, Pencil, Phone } from '@lucide/vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { edit as carriersEdit } from '@/routes/carriers';
import type { CarrierResource } from './carrier';

defineProps<{
    carrier: CarrierResource;
    policiesCount: number;
}>();
</script>

<template>
    <div
        class="flex flex-col items-start gap-4 pb-6 sm:flex-row sm:justify-between"
    >
        <div class="flex items-start gap-4">
            <Avatar :name="carrier.name" :size="64" />

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-semibold text-primary">
                        {{ carrier.name }}
                    </h1>
                    <Badge tone="accent">{{ policiesCount }} policies</Badge>
                </div>
                <div
                    class="mt-1.5 flex flex-wrap items-center gap-4 text-[13px] text-secondary"
                >
                    <span
                        v-if="carrier.phone"
                        class="inline-flex items-center gap-1.5"
                    >
                        <Phone class="size-3.5 text-tertiary" />
                        <span class="font-mono">{{ carrier.phone }}</span>
                    </span>
                    <span
                        v-if="carrier.website"
                        class="inline-flex items-center gap-1.5"
                    >
                        <Globe class="size-3.5 text-tertiary" />
                        <span class="font-mono text-accent">{{
                            carrier.website
                        }}</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <Link :href="carriersEdit(carrier.slug).url">
                <Button variant="secondary" size="md">
                    <template #leading><Pencil /></template>
                    Edit
                </Button>
            </Link>
        </div>
    </div>
</template>
