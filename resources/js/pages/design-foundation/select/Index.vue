<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import Label from '@/components/ui/label/Label.vue';
import Select from '@/components/ui/select/Select.vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import disabledCode from './snippets/disabled.md?raw';
import reactivityCode from './snippets/reactivity.md?raw';
import sizesCode from './snippets/sizes.md?raw';
import withLabelCode from './snippets/with-label.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'sizes', label: 'Sizes' },
    { id: 'disabled', label: 'Disabled' },
    { id: 'reactivity', label: 'Reactivity' },
    { id: 'with-label', label: 'Pairing with label' },
];

const views = ref<Record<string, ViewMode>>(
    Object.fromEntries(sections.map((s) => [s.id, 'preview'])),
);

const codeSnippets: Record<string, string> = {
    sizes: sizesCode,
    disabled: disabledCode,
    reactivity: reactivityCode,
    'with-label': withLabelCode,
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

const selected = ref('');
</script>

<template>
    <Head title="Select — Design Foundation" />

    <h1 class="mb-10 text-2xl font-semibold">Select</h1>

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
                    v-if="section.id === 'sizes'"
                    class="flex max-w-sm flex-col gap-3"
                >
                    <Select size="sm" placeholder="Small select">
                        <option value="apple">Apple</option>
                        <option value="banana">Banana</option>
                    </Select>
                    <Select size="md" placeholder="Medium select">
                        <option value="apple">Apple</option>
                        <option value="banana">Banana</option>
                    </Select>
                </div>

                <div v-else-if="section.id === 'disabled'" class="max-w-sm">
                    <Select placeholder="Disabled select" disabled>
                        <option value="apple">Apple</option>
                        <option value="banana">Banana</option>
                    </Select>
                </div>

                <div
                    v-else-if="section.id === 'reactivity'"
                    class="flex max-w-sm flex-col gap-3"
                >
                    <Select v-model="selected" placeholder="Pick a fruit">
                        <option value="apple">Apple</option>
                        <option value="banana">Banana</option>
                        <option value="cherry">Cherry</option>
                    </Select>
                    <p class="text-sm text-secondary">Value: {{ selected }}</p>
                </div>

                <div
                    v-else-if="section.id === 'with-label'"
                    class="flex max-w-sm flex-col gap-1.5"
                >
                    <Label for="fruit">Fruit</Label>
                    <Select id="fruit" placeholder="Pick a fruit">
                        <option value="apple">Apple</option>
                        <option value="banana">Banana</option>
                        <option value="cherry">Cherry</option>
                    </Select>
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
