<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import tonesCode from './snippets/tones.md?raw';
import withDotCode from './snippets/with-dot.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'tones', label: 'Tones' },
    { id: 'with-dot', label: 'With dot' },
];

const views = ref<Record<string, ViewMode>>(Object.fromEntries(sections.map((s) => [s.id, 'preview'])));

const codeSnippets: Record<string, string> = {
    tones: tonesCode,
    'with-dot': withDotCode,
};

const highlighted = ref<Record<string, string>>({});

onMounted(async () => {
    const entries = await Promise.all(
        Object.entries(codeSnippets).map(async ([id, code]) => {
            const html = await codeToHtml(code, { lang: 'vue', theme: 'github-dark' });

            return [id, html] as [string, string];
        }),
    );

    highlighted.value = Object.fromEntries(entries);
});
</script>

<template>
    <Head title="Badge — Design Foundation" />

    <h1 class="text-2xl font-semibold mb-10">Badge</h1>

    <div class="flex flex-col gap-10">
        <div v-for="section in sections" :id="section.id" :key="section.id" class="flex flex-col gap-3">
            <!-- Section header -->
            <div class="flex items-center justify-between">
                <p class="text-xs font-mono text-tertiary uppercase tracking-widest">{{ section.label }}</p>

                <div class="flex items-center gap-0.5 rounded-md border border-border p-0.5 text-xs">
                    <button
                        class="px-2.5 py-1 rounded transition-colors"
                        :class="
                            views[section.id] === 'preview' ? 'bg-surface text-primary' : 'text-tertiary hover:text-secondary'
                        "
                        @click="views[section.id] = 'preview'"
                    >
                        Preview
                    </button>
                    <button
                        class="px-2.5 py-1 rounded transition-colors"
                        :class="
                            views[section.id] === 'code' ? 'bg-surface text-primary' : 'text-tertiary hover:text-secondary'
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
                class="rounded-lg border border-border p-6 h-[300px] overflow-y-auto"
                style="background: #f8f8f8"
            >
                <div v-if="section.id === 'tones'" class="flex flex-wrap gap-3">
                    <Badge tone="neutral">Neutral</Badge>
                    <Badge tone="success">Success</Badge>
                    <Badge tone="warning">Warning</Badge>
                    <Badge tone="danger">Danger</Badge>
                    <Badge tone="info">Info</Badge>
                    <Badge tone="accent">Accent</Badge>
                </div>

                <div v-else-if="section.id === 'with-dot'" class="flex flex-wrap gap-3">
                    <Badge tone="neutral" dot>Neutral</Badge>
                    <Badge tone="success" dot>Success</Badge>
                    <Badge tone="warning" dot>Warning</Badge>
                    <Badge tone="danger" dot>Danger</Badge>
                    <Badge tone="info" dot>Info</Badge>
                    <Badge tone="accent" dot>Accent</Badge>
                </div>
            </div>

            <!-- Code panel -->
            <div
                v-else
                class="rounded-lg overflow-hidden border border-border text-sm h-[300px] overflow-y-auto [&>pre]:!m-0 [&>pre]:min-h-full [&>pre]:p-5 [&>pre]:leading-relaxed"
                v-html="highlighted[section.id] ?? ''"
            />
        </div>
    </div>
</template>
