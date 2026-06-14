<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AtSign, Search } from '@lucide/vue';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import disabledCode from './snippets/disabled.md?raw';
import leadingSlotCode from './snippets/leading-slot.md?raw';
import sizesCode from './snippets/sizes.md?raw';
import trailingSlotCode from './snippets/trailing-slot.md?raw';
import reactivityCode from './snippets/reactivity.md?raw';
import withLabelCode from './snippets/with-label.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'sizes', label: 'Sizes' },
    { id: 'leading-slot', label: 'Leading slot' },
    { id: 'trailing-slot', label: 'Trailing slot' },
    { id: 'disabled', label: 'Disabled' },
    { id: 'reactivity', label: 'Reactivity' },
    { id: 'with-label', label: 'Pairing with label' },
];

const views = ref<Record<string, ViewMode>>(Object.fromEntries(sections.map((s) => [s.id, 'preview'])));

const codeSnippets: Record<string, string> = {
    sizes: sizesCode,
    'leading-slot': leadingSlotCode,
    'trailing-slot': trailingSlotCode,
    disabled: disabledCode,
    reactivity: reactivityCode,
    'with-label': withLabelCode,
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

const vModelValue = ref('');
</script>

<template>
    <Head title="Input — Design Foundation" />

    <h1 class="text-2xl font-semibold mb-10">Input</h1>

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
                <div v-if="section.id === 'sizes'" class="flex flex-col gap-3 max-w-sm">
                    <Input size="sm" placeholder="Small input" />
                    <Input size="md" placeholder="Medium input" />
                </div>

                <div v-else-if="section.id === 'leading-slot'" class="max-w-sm">
                    <Input placeholder="Search...">
                        <template #leading><Search /></template>
                    </Input>
                </div>

                <div v-else-if="section.id === 'trailing-slot'" class="max-w-sm">
                    <Input placeholder="Enter email">
                        <template #trailing><AtSign /></template>
                    </Input>
                </div>

                <div v-else-if="section.id === 'disabled'" class="max-w-sm">
                    <Input placeholder="Disabled input" disabled />
                </div>

                <div v-else-if="section.id === 'reactivity'" class="flex flex-col gap-3 max-w-sm">
                    <Input v-model="vModelValue" placeholder="Type something..." />
                    <p class="text-sm text-secondary">Value: {{ vModelValue }}</p>
                </div>

                <div v-else-if="section.id === 'with-label'" class="flex flex-col gap-1.5 max-w-sm">
                    <Label for="name">Full name</Label>
                    <Input id="name" placeholder="John Doe" />
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
