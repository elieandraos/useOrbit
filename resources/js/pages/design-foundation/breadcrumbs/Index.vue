<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import type { BreadcrumbItem } from '@/types';
import basicCode from './snippets/basic.md?raw';
import defineOptionsCode from './snippets/define-options.md?raw';
import setLayoutPropsCode from './snippets/set-layout-props.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'basic', label: 'Basic' },
    { id: 'define-options', label: 'Static — defineOptions' },
    { id: 'set-layout-props', label: 'Dynamic — setLayoutProps' },
];

const views = ref<Record<string, ViewMode>>(
    Object.fromEntries(sections.map((s) => [s.id, 'preview'])),
);

const codeSnippets: Record<string, string> = {
    basic: basicCode,
    'define-options': defineOptionsCode,
    'set-layout-props': setLayoutPropsCode,
};

const highlighted = ref<Record<string, string>>({});

onMounted(async () => {
    const entries = await Promise.all(
        Object.entries(codeSnippets).map(async ([id, code]) => {
            const html = await codeToHtml(code, {
                lang: 'vue',
                theme: 'github-dark',
            });

            return [id, html] as [string, string];
        }),
    );
    highlighted.value = Object.fromEntries(entries);
});

const previewBreadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
    { title: 'Create' },
];

const previewDeepBreadcrumbs: BreadcrumbItem[] = [
    { title: 'Clients', href: '/clients' },
    { title: 'Acme Corp', href: '/clients/acme-corp' },
    { title: 'Edit' },
];
</script>

<template>
    <Head title="Breadcrumbs — Design Foundation" />

    <h1 class="mb-10 text-2xl font-semibold">Breadcrumbs</h1>

    <div class="flex flex-col gap-10">
        <div
            v-for="section in sections"
            :id="section.id"
            :key="section.id"
            class="flex flex-col gap-3"
        >
            <!-- Section header -->
            <div class="flex items-center justify-between">
                <p
                    class="font-mono text-xs tracking-widest text-tertiary uppercase"
                >
                    {{ section.label }}
                </p>

                <div
                    class="flex items-center gap-0.5 rounded-md border border-border p-0.5 text-xs"
                >
                    <button
                        class="rounded px-2.5 py-1 transition-colors"
                        :class="
                            views[section.id] === 'preview'
                                ? 'bg-surface text-primary'
                                : 'text-tertiary hover:text-secondary'
                        "
                        @click="views[section.id] = 'preview'"
                    >
                        Preview
                    </button>
                    <button
                        class="rounded px-2.5 py-1 transition-colors"
                        :class="
                            views[section.id] === 'code'
                                ? 'bg-surface text-primary'
                                : 'text-tertiary hover:text-secondary'
                        "
                        @click="views[section.id] = 'code'"
                    >
                        Code
                    </button>
                </div>
            </div>

            <!-- Preview panel -->
            <div
                v-if="views[section.id] === 'preview'"
                class="h-[300px] overflow-y-auto rounded-lg border border-border p-6"
                style="background: #f8f8f8"
            >
                <div v-if="section.id === 'basic'">
                    <Breadcrumbs :breadcrumbs="previewBreadcrumbs" />
                </div>

                <div v-else-if="section.id === 'define-options'">
                    <Breadcrumbs :breadcrumbs="previewBreadcrumbs" />
                    <p class="mt-4 text-xs text-secondary">
                        Use <code class="font-mono">defineOptions</code> when
                        breadcrumb titles and hrefs are known at compile time
                        (no runtime props needed).
                    </p>
                </div>

                <div v-else-if="section.id === 'set-layout-props'">
                    <Breadcrumbs :breadcrumbs="previewDeepBreadcrumbs" />
                    <p class="mt-4 text-xs text-secondary">
                        Use <code class="font-mono">setLayoutProps</code> when
                        breadcrumbs depend on
                        <code class="font-mono">defineProps</code> values (e.g.
                        a slug or name from the server).
                    </p>
                </div>
            </div>

            <!-- Code panel -->
            <div
                v-else
                class="h-[300px] overflow-hidden overflow-y-auto rounded-lg border border-border text-sm [&>pre]:!m-0 [&>pre]:min-h-full [&>pre]:p-5 [&>pre]:leading-relaxed"
                v-html="highlighted[section.id] ?? ''"
            />
        </div>
    </div>
</template>
