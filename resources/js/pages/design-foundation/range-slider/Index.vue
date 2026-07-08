<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import RangeSlider from '@/components/ui/range-slider/RangeSlider.vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import basicCode from './snippets/basic.md?raw';
import stepCode from './snippets/step.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'basic', label: 'Basic' },
    { id: 'step', label: 'Step' },
];

const views = ref<Record<string, ViewMode>>(
    Object.fromEntries(sections.map((s) => [s.id, 'preview'])),
);

const codeSnippets: Record<string, string> = {
    basic: basicCode,
    step: stepCode,
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

const ageRange = ref<[number, number]>([26, 72]);
const priceRange = ref<[number, number]>([20, 80]);
</script>

<template>
    <Head title="RangeSlider — Design Foundation" />

    <h1 class="mb-10 text-2xl font-semibold">RangeSlider</h1>

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
                <div
                    v-if="section.id === 'basic'"
                    class="flex max-w-xs flex-col gap-2"
                >
                    <p class="text-sm text-secondary">
                        {{ ageRange[0] }} – {{ ageRange[1] }} yrs
                    </p>
                    <RangeSlider v-model="ageRange" :min="18" :max="90" />
                </div>

                <div
                    v-else-if="section.id === 'step'"
                    class="flex max-w-xs flex-col gap-2"
                >
                    <p class="text-sm text-secondary">
                        ${{ priceRange[0] }} – ${{ priceRange[1] }}
                    </p>
                    <RangeSlider
                        v-model="priceRange"
                        :min="0"
                        :max="100"
                        :step="5"
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
