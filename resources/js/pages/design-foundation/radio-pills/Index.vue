<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import RadioPills from '@/components/ui/radio-pills/RadioPills.vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import reactivityCode from './snippets/reactivity.md?raw';
import sizesCode from './snippets/sizes.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'reactivity', label: 'Reactivity' },
    { id: 'sizes', label: 'Sizes' },
];

const views = ref<Record<string, ViewMode>>(Object.fromEntries(sections.map((s) => [s.id, 'preview'])));

const codeSnippets: Record<string, string> = {
    reactivity: reactivityCode,
    sizes: sizesCode,
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

const reactivitySelected = ref('Female');
const sizeSmSelected = ref('Female');
const sizeMdSelected = ref('Female');

const genderOptions = ['Female', 'Male', 'Non-binary', 'Prefer not to say'];
</script>

<template>
    <Head title="RadioPills — Design Foundation" />

    <h1 class="text-2xl font-semibold mb-10">RadioPills</h1>

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
                <div v-if="section.id === 'reactivity'" class="flex flex-col gap-4">
                    <RadioPills v-model="reactivitySelected" :options="genderOptions" />
                    <p class="text-sm text-secondary">Selected: {{ reactivitySelected }}</p>
                </div>

                <div v-else-if="section.id === 'sizes'" class="flex flex-col gap-4">
                    <RadioPills v-model="sizeSmSelected" :options="genderOptions" size="sm" />
                    <RadioPills v-model="sizeMdSelected" :options="genderOptions" size="md" />
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