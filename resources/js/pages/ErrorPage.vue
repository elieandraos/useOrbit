<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Construction,
    FileQuestion,
    ServerCrash,
    ShieldAlert,
    TriangleAlert,
} from '@lucide/vue';
import { computed } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import { dashboard, login } from '@/routes';

const props = defineProps<{
    status: number;
}>();

const content = computed(() => {
    switch (props.status) {
        case 403:
            return {
                icon: ShieldAlert,
                title: "You don't have access to this page",
                description:
                    "You don't have permission to view this page. If you think this is a mistake, contact your organization's owner.",
            };
        case 404:
            return {
                icon: FileQuestion,
                title: 'Page not found',
                description:
                    "The page you're looking for doesn't exist or may have been moved.",
            };
        case 500:
            return {
                icon: ServerCrash,
                title: 'Something went wrong',
                description:
                    'An unexpected error occurred on our end. Please try again in a moment.',
            };
        case 503:
            return {
                icon: Construction,
                title: 'Down for maintenance',
                description:
                    "We're performing scheduled maintenance. Please check back shortly.",
            };
        default:
            return {
                icon: TriangleAlert,
                title: 'Unexpected error',
                description: 'Something went wrong while loading this page.',
            };
    }
});
</script>

<template>
    <Head :title="content.title" />

    <div class="flex min-h-screen flex-1 items-center justify-center py-16">
        <div
            class="w-full max-w-[480px] rounded-lg border border-border bg-surface px-9 py-11 text-center shadow-card"
        >
            <div
                class="mx-auto mb-4 flex size-14 items-center justify-center rounded-lg bg-accent-bg text-accent"
            >
                <component :is="content.icon" class="size-6" />
            </div>
            <h2 class="text-lg font-semibold text-primary">
                {{ content.title }}
            </h2>
            <p
                class="mx-auto mt-2 max-w-[360px] text-sm leading-relaxed text-secondary"
            >
                {{ content.description }}
            </p>
            <div class="mt-6 flex justify-center">
                <Link :href="$page.props.auth.user ? dashboard() : login()">
                    <Button variant="primary" size="md">
                        {{
                            $page.props.auth.user
                                ? 'Back to dashboard'
                                : 'Log in'
                        }}
                    </Button>
                </Link>
            </div>
        </div>
    </div>
</template>
