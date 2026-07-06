<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
import titleOnlyCode from './snippets/title-only.md?raw';
import titleAndSubtitleCode from './snippets/title-and-subtitle.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'title-only', label: 'With title only' },
    { id: 'title-and-subtitle', label: 'With title and subtitle' },
];

const views = ref<Record<string, ViewMode>>(Object.fromEntries(sections.map((s) => [s.id, 'preview'])));

const codeSnippets: Record<string, string> = {
    'title-only': titleOnlyCode,
    'title-and-subtitle': titleAndSubtitleCode,
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
    <Head title="FormSection — Design Foundation" />

    <h1 class="text-2xl font-semibold mb-10">FormSection</h1>

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
                <div v-if="section.id === 'title-only'" class="max-w-sm">
                    <FormSection title="Personal information">
                        <FormField label="Full name" for="name">
                            <Input id="name" placeholder="John Doe" />
                        </FormField>
                    </FormSection>
                </div>

                <div v-else-if="section.id === 'title-and-subtitle'" class="max-w-sm">
                    <FormSection title="Emergency contact" subtitle="Person to reach in case of an emergency.">
                        <FormField label="Full name" for="emergency-name">
                            <Input id="emergency-name" placeholder="Jane Doe" />
                        </FormField>
                    </FormSection>
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
