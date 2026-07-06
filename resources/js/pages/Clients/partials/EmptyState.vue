<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Plus, Users } from '@lucide/vue';
import Button from '@/components/ui/button/Button.vue';
import { create as clientsCreate } from '@/routes/clients';
</script>

<template>
    <div class="flex flex-1 items-center justify-center py-16">
        <div
            class="w-full max-w-[480px] rounded-lg border border-border bg-surface px-9 py-11 text-center shadow-card"
        >
            <div
                class="mx-auto mb-4 flex size-14 items-center justify-center rounded-lg bg-accent-bg text-accent"
            >
                <Users class="size-6" />
            </div>
            <h2 class="text-lg font-semibold text-primary">No clients yet</h2>
            <p
                class="mx-auto mt-2 max-w-[360px] text-sm leading-relaxed text-secondary"
            >
                Add your first client to start tracking policies, renewals, and
                household details across health, auto, home, and commercial
                lines.
            </p>
            <div class="mt-6 flex justify-center">
                <Link :href="clientsCreate().url">
                    <Button variant="primary" size="md">
                        <template #leading><Plus /></template>
                        Add New Client
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
