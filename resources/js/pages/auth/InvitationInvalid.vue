<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Invitation no longer valid',
        description: 'This invitation has expired or has already been used',
    },
});
</script>

<template>
    <Head title="Invitation unavailable" />

    <div class="flex flex-col items-center gap-6">
        <span
            class="inline-flex size-10 items-center justify-center rounded-full bg-warning-bg text-warning"
        >
            <TriangleAlert class="size-5" />
        </span>

        <p class="text-center text-sm text-secondary">
            Ask whoever invited you to send a new invitation, or sign in if you
            already have an account.
        </p>

        <Link :href="login()" class="w-full">
            <Button variant="secondary" size="lg" full>Go to sign in</Button>
        </Link>
    </div>
</template>
