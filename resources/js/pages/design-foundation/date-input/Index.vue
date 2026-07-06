<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import DateInput from '@/components/ui/date-input/DateInput.vue';
import sizesCode from './snippets/sizes.md?raw';
import reactivityCode from './snippets/reactivity.md?raw';
import preFilledCode from './snippets/pre-filled.md?raw';
import startYearCode from './snippets/start-year.md?raw';
import endYearCode from './snippets/end-year.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'sizes', label: 'Sizes' },
    { id: 'reactivity', label: 'Reactivity' },
    { id: 'pre-filled', label: 'Pre-filled' },
    { id: 'start-year', label: 'Start year' },
    { id: 'end-year', label: 'End year' },
];

const views = ref<Record<string, ViewMode>>(Object.fromEntries(sections.map((s) => [s.id, 'preview'])));

const codeSnippets: Record<string, string> = {
    sizes: sizesCode,
    reactivity: reactivityCode,
    'pre-filled': preFilledCode,
    'start-year': startYearCode,
    'end-year': endYearCode,
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

const date = ref('');
const preFilledDate = ref('1990-05-15');
const startYearDate = ref('1965-06-10');
const endYearDate = ref('');
</script>

<template>
    <Head title="DateInput — Design Foundation" />

    <h1 class="text-2xl font-semibold mb-10">DateInput</h1>

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
                <div v-if="section.id === 'sizes'" class="flex flex-col gap-3">
                    <DateInput size="sm" />
                    <DateInput size="md" />
                </div>

                <div v-else-if="section.id === 'reactivity'" class="flex flex-col gap-3">
                    <DateInput v-model="date" />
                    <p class="text-sm text-secondary">Value: {{ date || '—' }}</p>
                </div>

                <div v-else-if="section.id === 'pre-filled'" class="flex flex-col gap-3">
                    <DateInput v-model="preFilledDate" />
                    <p class="text-sm text-secondary">Value: {{ preFilledDate }}</p>
                </div>

                <div v-else-if="section.id === 'start-year'" class="flex flex-col gap-3">
                    <DateInput v-model="startYearDate" :start-year="1950" />
                    <p class="text-sm text-secondary">Value: {{ startYearDate || '—' }}</p>
                </div>

                <div v-else-if="section.id === 'end-year'" class="flex flex-col gap-3">
                    <DateInput v-model="endYearDate" :end-year="2030" />
                    <p class="text-sm text-secondary">Value: {{ endYearDate || '—' }}</p>
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
