<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const navItems = [{ label: 'Button', href: '/design-foundation/button' }];
</script>

<template>
    <div class="flex min-h-screen bg-background text-primary">
        <!-- Left nav -->
        <nav class="fixed inset-y-0 left-0 w-52 border-r border-border bg-background flex flex-col px-4 py-8 gap-6">
            <span class="text-sm font-semibold tracking-tight">Design Foundation</span>

            <div class="flex flex-col gap-1">
                <p class="text-[10px] font-mono text-tertiary uppercase tracking-widest mb-2">Components</p>

                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="text-sm px-3 py-1.5 rounded-md transition-colors"
                    :class="
                        page.url.startsWith(item.href)
                            ? 'font-medium text-primary bg-surface'
                            : 'text-tertiary hover:text-secondary'
                    "
                >
                    {{ item.label }}
                </Link>
            </div>
        </nav>

        <!-- Page content -->
        <main class="ml-52 flex-1 px-12 py-12 max-w-4xl">
            <slot />
        </main>
    </div>
</template>
