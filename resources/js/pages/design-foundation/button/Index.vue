<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowRight, Plus } from '@lucide/vue';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import Spinner from '@/components/ui/spinner/Spinner.vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import disabledCode from './snippets/disabled.md?raw';
import fullWidthCode from './snippets/full-width.md?raw';
import leadingSlotCode from './snippets/leading-slot.md?raw';
import loadingCode from './snippets/loading.md?raw';
import sizesCode from './snippets/sizes.md?raw';
import trailingSlotCode from './snippets/trailing-slot.md?raw';
import variantsCode from './snippets/variants.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'variants', label: 'Variants' },
    { id: 'sizes', label: 'Sizes' },
    { id: 'leading-slot', label: 'Leading slot' },
    { id: 'trailing-slot', label: 'Trailing slot' },
    { id: 'full-width', label: 'Full width' },
    { id: 'disabled', label: 'Disabled' },
    { id: 'loading', label: 'Loading' },
];

const views = ref<Record<string, ViewMode>>(
    Object.fromEntries(sections.map((s) => [s.id, 'preview'])),
);

const codeSnippets: Record<string, string> = {
    variants: variantsCode,
    sizes: sizesCode,
    'leading-slot': leadingSlotCode,
    'trailing-slot': trailingSlotCode,
    'full-width': fullWidthCode,
    disabled: disabledCode,
    loading: loadingCode,
};

const highlighted = ref<Record<string, string>>({});

const isLoading = ref(false);

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
</script>

<template>
    <Head title="Button — Design Foundation" />

    <h1 class="mb-10 text-2xl font-semibold">Button</h1>

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
                    v-if="section.id === 'variants'"
                    class="flex flex-wrap gap-3"
                >
                    <Button variant="primary">Primary</Button>
                    <Button variant="secondary">Secondary</Button>
                    <Button variant="ghost">Ghost</Button>
                    <Button variant="destructive">Destructive</Button>
                </div>

                <div
                    v-else-if="section.id === 'sizes'"
                    class="flex flex-wrap items-center gap-3"
                >
                    <Button size="sm">Small</Button>
                    <Button size="md">Medium</Button>
                    <Button size="lg">Large</Button>
                </div>

                <div
                    v-else-if="section.id === 'leading-slot'"
                    class="flex flex-wrap gap-3"
                >
                    <Button>
                        <template #leading><Plus /></template>
                        Add item
                    </Button>
                </div>

                <div
                    v-else-if="section.id === 'trailing-slot'"
                    class="flex flex-wrap gap-3"
                >
                    <Button>
                        Continue
                        <template #trailing><ArrowRight /></template>
                    </Button>
                </div>

                <div v-else-if="section.id === 'full-width'">
                    <Button :full="true">Full width button</Button>
                </div>

                <div
                    v-else-if="section.id === 'disabled'"
                    class="flex flex-wrap gap-3"
                >
                    <Button variant="primary" disabled>Primary</Button>
                    <Button variant="secondary" disabled>Secondary</Button>
                    <Button variant="ghost" disabled>Ghost</Button>
                    <Button variant="destructive" disabled>Destructive</Button>
                </div>

                <div v-else-if="section.id === 'loading'">
                    <Button
                        :disabled="isLoading"
                        @click="isLoading = !isLoading"
                    >
                        <template v-if="isLoading" #leading>
                            <Spinner />
                        </template>
                        {{ isLoading ? 'Saving...' : 'Save changes' }}
                    </Button>
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
