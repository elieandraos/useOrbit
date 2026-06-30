<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import reactivityCode from './snippets/reactivity.md?raw';
import verticalCode from './snippets/vertical.md?raw';
import sizesCode from './snippets/sizes.md?raw';
import roleSelectionCode from './snippets/role-selection.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'reactivity', label: 'Reactivity' },
    { id: 'sizes', label: 'Sizes' },
    { id: 'vertical', label: 'Vertical display' },
    { id: 'role-selection', label: 'With description' },
];

const views = ref<Record<string, ViewMode>>(Object.fromEntries(sections.map((s) => [s.id, 'preview'])));

const codeSnippets: Record<string, string> = {
    reactivity: reactivityCode,
    sizes: sizesCode,
    vertical: verticalCode,
    'role-selection': roleSelectionCode,
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
const verticalSelected = ref('Female');
const roleSelected = ref('member');

const genderOptions = ['Female', 'Male', 'Non-binary', 'Prefer not to say'];
const roleOptions = [
    { label: 'Owner', value: 'owner', desc: 'Full access including member management and billing' },
    { label: 'Member', value: 'member', desc: 'Can view and manage clients but cannot delete or manage members' },
];
</script>

<template>
    <Head title="RadioChips — Design Foundation" />

    <h1 class="text-2xl font-semibold mb-10">RadioChips</h1>

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
                    <RadioChips v-model="reactivitySelected" :options="genderOptions" />
                    <p class="text-sm text-secondary">Selected: {{ reactivitySelected }}</p>
                </div>

                <div v-else-if="section.id === 'sizes'" class="flex flex-col gap-4">
                    <RadioChips v-model="sizeSmSelected" :options="genderOptions" size="sm" />
                    <RadioChips v-model="sizeMdSelected" :options="genderOptions" size="md" />
                </div>

                <div v-else-if="section.id === 'vertical'">
                    <RadioChips v-model="verticalSelected" :options="genderOptions" direction="vertical" />
                </div>

                <div v-else-if="section.id === 'role-selection'">
                    <RadioChips v-model="roleSelected" :options="roleOptions" direction="vertical" />
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
