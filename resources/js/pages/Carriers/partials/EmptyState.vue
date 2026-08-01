<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Building2, Plus } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
import { create as carriersCreate } from '@/routes/carriers';
</script>

<template>
    <div class="flex flex-1 items-center justify-center py-16">
        <div
            class="w-full max-w-[540px] rounded-lg border border-border bg-surface px-9 py-11 text-center shadow-card"
        >
            <div
                class="mx-auto mb-4 flex size-14 items-center justify-center rounded-lg bg-accent-bg text-accent"
            >
                <Building2 class="size-6" />
            </div>
            <h2 class="text-lg font-semibold text-primary">No carriers yet</h2>
            <p
                class="mx-auto mt-2 max-w-[380px] text-sm leading-relaxed text-secondary"
            >
                Add your first insurance carrier. Capture branches, contact
                people, and the policies you'll renew with them.
            </p>
            <div class="mt-6 flex justify-center">
                <Link :href="carriersCreate().url">
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        Add new carrier
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
