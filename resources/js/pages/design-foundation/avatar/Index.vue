<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import sizesCode from './snippets/sizes.md?raw';
import colorsCode from './snippets/colors.md?raw';
import imageCode from './snippets/image.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'sizes', label: 'Sizes' },
    { id: 'colors', label: 'Colors (deterministic hue)' },
    { id: 'image', label: 'Image' },
];

const views = ref<Record<string, ViewMode>>(Object.fromEntries(sections.map((s) => [s.id, 'preview'])));

const codeSnippets: Record<string, string> = {
    sizes: sizesCode,
    colors: colorsCode,
    image: imageCode,
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
    <Head title="Avatar — Design Foundation" />

    <h1 class="text-2xl font-semibold mb-10">Avatar</h1>

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
                <div v-if="section.id === 'sizes'" class="flex items-center gap-4">
                    <Avatar name="Alice Johnson" size="sm" />
                    <Avatar name="Alice Johnson" size="md" />
                    <Avatar name="Alice Johnson" size="lg" />
                </div>

                <div v-else-if="section.id === 'colors'" class="flex items-center gap-3">
                    <Avatar name="Alice Johnson" />
                    <Avatar name="Bob Smith" />
                    <Avatar name="Carol Williams" />
                    <Avatar name="David Chen" />
                    <Avatar name="Eva Martinez" />
                </div>

                <div v-else-if="section.id === 'image'" class="flex items-center gap-3">
                    <Avatar name="Alice Johnson" src="https://i.pravatar.cc/150?img=47" />
                    <Avatar name="Alice Johnson" src="https://invalid-url.example/broken.jpg" />
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
