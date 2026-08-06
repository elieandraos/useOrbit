<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Invitation unavailable',
        description: 'This invitation link is invalid or has expired',
    },
});
</script>

<template>
    <Head title="Invitation unavailable" />

    <div class="flex flex-col items-center gap-4 text-center text-sm text-secondary">
        <p>
            Ask whoever invited you to send a new invitation, or sign in if
            you already have an account.
        </p>
        <TextLink :href="login()">Go to sign in</TextLink>
    </div>
</template>
