<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import { Pagination } from '@/components/ui/pagination';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import defaultCode from './snippets/default.md?raw';
import firstPageCode from './snippets/first-page.md?raw';
import lastPageCode from './snippets/last-page.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'default', label: 'Default' },
    { id: 'first-page', label: 'First page' },
    { id: 'last-page', label: 'Last page' },
];

const views = ref<Record<string, ViewMode>>(
    Object.fromEntries(sections.map((s) => [s.id, 'preview'])),
);

const codeSnippets: Record<string, string> = {
    default: defaultCode,
    'first-page': firstPageCode,
    'last-page': lastPageCode,
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

const defaultMeta = {
    current_page: 2,
    last_page: 4,
    from: 16,
    to: 30,
    total: 62,
    per_page: 15,
    links: [
        {
            url: '/design-foundation/pagination?page=1',
            label: '&laquo; Previous',
            active: false,
        },
        {
            url: '/design-foundation/pagination?page=1',
            label: '1',
            active: false,
        },
        {
            url: '/design-foundation/pagination?page=2',
            label: '2',
            active: true,
        },
        {
            url: '/design-foundation/pagination?page=3',
            label: '3',
            active: false,
        },
        {
            url: '/design-foundation/pagination?page=4',
            label: '4',
            active: false,
        },
        {
            url: '/design-foundation/pagination?page=3',
            label: 'Next &raquo;',
            active: false,
        },
    ],
};

const firstPageMeta = {
    current_page: 1,
    last_page: 3,
    from: 1,
    to: 15,
    total: 42,
    per_page: 15,
    links: [
        { url: null, label: '&laquo; Previous', active: false },
        {
            url: '/design-foundation/pagination?page=1',
            label: '1',
            active: true,
        },
        {
            url: '/design-foundation/pagination?page=2',
            label: '2',
            active: false,
        },
        {
            url: '/design-foundation/pagination?page=3',
            label: '3',
            active: false,
        },
        {
            url: '/design-foundation/pagination?page=2',
            label: 'Next &raquo;',
            active: false,
        },
    ],
};

const lastPageMeta = {
    current_page: 3,
    last_page: 3,
    from: 31,
    to: 42,
    total: 42,
    per_page: 15,
    links: [
        {
            url: '/design-foundation/pagination?page=2',
            label: '&laquo; Previous',
            active: false,
        },
        {
            url: '/design-foundation/pagination?page=1',
            label: '1',
            active: false,
        },
        {
            url: '/design-foundation/pagination?page=2',
            label: '2',
            active: false,
        },
        {
            url: '/design-foundation/pagination?page=3',
            label: '3',
            active: true,
        },
        { url: null, label: 'Next &raquo;', active: false },
    ],
};
</script>

<template>
    <Head title="Pagination — Design Foundation" />

    <h1 class="mb-10 text-2xl font-semibold">Pagination</h1>

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
                class="flex items-center justify-center overflow-hidden rounded-lg border border-border"
                style="background: #f8f8f8"
            >
                <div class="w-full max-w-xl bg-surface">
                    <Pagination
                        v-if="section.id === 'default'"
                        :meta="defaultMeta"
                        item-label="clients"
                    />
                    <Pagination
                        v-else-if="section.id === 'first-page'"
                        :meta="firstPageMeta"
                        item-label="clients"
                    />
                    <Pagination
                        v-else-if="section.id === 'last-page'"
                        :meta="lastPageMeta"
                        item-label="clients"
                    />
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
